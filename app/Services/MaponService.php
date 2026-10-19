<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the MAPON tachograph API.
 *
 * Auth: query-string API key (?key=…).
 * Base URL + key come from config('services.mapon').
 *
 * Only two endpoints are needed for the "Tacho Activity" admin report:
 *   - GET /driver/list                     → list of all drivers in the MAPON account
 *   - GET /driver/daily_activities         → activity intervals for one driver in a date range
 *
 * Doc: https://mapon.com/api/
 */
class MaponService
{
    protected Client $http;
    protected ?string $key;
    protected string $baseUrl;

    public function __construct()
    {
        $this->key = config('services.mapon.key');
        $this->baseUrl = rtrim(config('services.mapon.base_url'), '/');
        $this->http = new Client([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => 30,
        ]);
    }

    public function isConfigured(): bool
    {
        return !empty($this->key);
    }

    /**
     * Fetch every driver in the MAPON account.
     * Returns the raw array of driver records as returned by the API.
     * Response shape varies slightly between MAPON tenants — caller should
     * normalize using firstName/lastName or `name` with fallbacks.
     */
    public function listDrivers(): array
    {
        $raw = $this->get('driver/list');
        $data = $raw['data'] ?? $raw;

        $drivers = [];

        // Shape A: { data: { drivers: [{id, name, ...}, ...] } }
        if (isset($data['drivers']) && is_array($data['drivers'])) {
            $drivers = $data['drivers'];
        }
        // Shape B: { data: { units: [{driver: {...}}, ...] } }
        elseif (isset($data['units']) && is_array($data['units'])) {
            foreach ($data['units'] as $unit) {
                if (!empty($unit['driver'])) $drivers[] = $unit['driver'];
            }
        }
        // Shape C: { data: [{id, name, ...}, ...] } — bare array
        elseif (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $drivers = $data;
        }

        Log::info('MAPON driver/list parsed', [
            'top_level_keys' => array_keys(is_array($raw) ? $raw : []),
            'data_keys' => is_array($data) ? array_keys($data) : [],
            'drivers_count' => count($drivers),
            'first_driver_sample' => $drivers[0] ?? null,
        ]);

        return $drivers;
    }

    /**
     * Get daily activity intervals for a single driver.
     *
     * IMPORTANT (from the sister tacho app's hard-won experience):
     * - `from`/`till` must be ISO 8601 UTC datetime (not YYYY-MM-DD): "2026-09-07T00:00:00Z"
     * - Max 31 days per request; the range is snapped to whole days
     * - Response is a BARE JSON ARRAY of days — NO `data` wrapper (unlike driver/list)
     * - Errors can arrive with HTTP 200 as `{"error":{"code":…,"msg":…}}`
     */
    public function dailyActivities($driverId, string $from, string $till): array
    {
        // Accept YYYY-MM-DD and promote to ISO datetime; pass through if already ISO.
        $fromIso = $this->toIsoUtc($from);
        $tillIso = $this->toIsoUtc($till, '23:59:59');

        $data = $this->get('driver/daily_activities', [
            'driver' => (string) $driverId,
            'from' => $fromIso,
            'till' => $tillIso,
        ]);

        // Bare array response → return as-is. Wrapped response → unwrap.
        if (isset($data['data'])) return (array) $data['data'];
        return $data;
    }

    /**
     * Returns the latest "active" date (YYYY-MM-DD, local tz) in the range for a driver,
     * or null if the driver has NO qualifying activity. Rules from the tacho app:
     *   - count only status in {DRIVING, WORK, AVAILABLE}
     *   - require end > start (reject zero/negative length records)
     *   - ignore REST + source=unkn (Mapon's gap filler = "no data", not rest)
     *   - ignore unknown status strings like "0" and card events
     */
    public function latestActivityDate($driverId, string $from, string $till, string $tz = 'Europe/Copenhagen'): ?string
    {
        $response = $this->dailyActivities($driverId, $from, $till);

        // Normalize: a single day object becomes a list of one.
        $days = isset($response['activities']) ? [$response] : array_values(array_filter($response, 'is_array'));

        $latestEnd = null;
        foreach ($days as $day) {
            foreach (($day['activities'] ?? []) as $a) {
                $status = strtoupper(trim((string) ($a['status'] ?? '')));
                $start = (int) ($a['start'] ?? 0);
                $end = (int) ($a['end'] ?? 0);

                if (!in_array($status, ['DRIVING', 'WORK', 'AVAILABLE'], true)) continue;
                if ($end <= $start) continue;

                if ($latestEnd === null || $end > $latestEnd) $latestEnd = $end;
            }
        }

        if ($latestEnd === null) return null;

        try {
            return (new \DateTimeImmutable('@' . $latestEnd))
                ->setTimezone(new \DateTimeZone($tz))
                ->format('Y-m-d');
        } catch (\Throwable $e) {
            return date('Y-m-d', $latestEnd);
        }
    }

    private function toIsoUtc(string $input, string $defaultTime = '00:00:00'): string
    {
        // Already ISO? pass through.
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $input)) return $input;
        // Plain date → datetime at midnight UTC (or end-of-day for till).
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) return $input . 'T' . $defaultTime . 'Z';
        // Fall back to Carbon/strtotime.
        $ts = strtotime($input);
        return gmdate('Y-m-d\TH:i:s\Z', $ts ?: time());
    }

    // ---- internal ----

    private function get(string $path, array $query = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('MAPON_API_KEY is not configured.');
        }
        $query['key'] = $this->key;

        try {
            $resp = $this->http->get($path . '.json', ['query' => $query]);
            $body = (string) $resp->getBody();
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                throw new \RuntimeException("MAPON {$path}: non-JSON response");
            }
            // MAPON reports errors in the body — sometimes with HTTP 200.
            if (isset($decoded['error'])) {
                $code = (int) ($decoded['error']['code'] ?? 0);
                $msg = (string) ($decoded['error']['msg'] ?? 'Unknown MAPON error');
                throw new \RuntimeException("MAPON {$path} error {$code}: {$msg}");
            }
            return $decoded;
        } catch (GuzzleException $e) {
            Log::warning('MAPON API call failed', [
                'path' => $path,
                'query' => array_diff_key($query, ['key' => true]),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("MAPON {$path}: " . $e->getMessage(), 0, $e);
        }
    }
}
