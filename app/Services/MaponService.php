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
     * Get daily activity intervals for a single driver between two dates (inclusive).
     * @param  string|int  $driverId  MAPON driver id
     * @param  string      $from      ISO date (YYYY-MM-DD) or ISO datetime
     * @param  string      $till      ISO date (YYYY-MM-DD) or ISO datetime
     * @return array                  Raw `data` payload from MAPON (empty array on failure).
     */
    public function dailyActivities($driverId, string $from, string $till): array
    {
        $data = $this->get('driver/daily_activities', [
            'driver' => (string) $driverId,
            'from' => $from,
            'till' => $till,
        ]);
        return $data['data'] ?? [];
    }

    /**
     * Returns the latest activity date (YYYY-MM-DD) in the given range for a driver,
     * or null if the driver has no activity in that window.
     * Robust against the two most common MAPON response shapes.
     */
    public function latestActivityDate($driverId, string $from, string $till): ?string
    {
        $data = $this->dailyActivities($driverId, $from, $till);
        $latest = $this->extractLatestDate($data);

        // One-time debug log for the FIRST non-empty response, success or failure, so we
        // can confirm the actual shape. Clears itself once we see data with a valid date.
        static $loggedFirstNonEmpty = false;
        if (!$loggedFirstNonEmpty && !empty($data)) {
            Log::info('MAPON daily_activities — sample payload', [
                'driver_id' => $driverId,
                'latest_extracted' => $latest,
                'top_keys' => is_array($data) ? array_keys($data) : [],
                'sample' => $this->truncateForLog($data),
            ]);
            $loggedFirstNonEmpty = true;
        }

        return $latest;
    }

    /**
     * Pull a YYYY-MM-DD date out of whatever shape MAPON gives us for daily_activities.
     * Looks for timestamps on days/intervals/activities and returns the latest found.
     */
    private function extractLatestDate(array $data): ?string
    {
        $latest = null;
        $this->walkForDates($data, $latest);
        return $latest;
    }

    /**
     * Recursively walk the response looking for anything that smells like a date.
     * Picks up keys named 'date', 'start', 'from', 'ts', 'timestamp' at any depth.
     * Flexible enough to handle MAPON shape variants without re-coding each one.
     */
    private function walkForDates($node, ?string &$latest): void
    {
        if (!is_array($node)) return;

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->walkForDates($value, $latest);
                continue;
            }
            // Only consider string/int date-ish values on known keys.
            if (!is_string($value) && !is_int($value)) continue;

            $date = null;
            if (in_array($key, ['date', 'day'], true) && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                $date = substr($value, 0, 10);
            } elseif (in_array($key, ['start', 'from', 'till', 'end', 'start_time', 'end_time'], true) && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                $date = substr($value, 0, 10);
            } elseif (in_array($key, ['ts', 'timestamp', 'start_ts', 'end_ts'], true) && is_numeric($value) && (int) $value > 1_000_000_000) {
                $date = date('Y-m-d', (int) $value);
            }

            if ($date !== null && ($latest === null || $date > $latest)) {
                $latest = $date;
            }
        }
    }

    private function truncateForLog($data): string
    {
        $json = json_encode($data);
        if (strlen($json) > 2000) $json = substr($json, 0, 2000) . '…(truncated)';
        return $json;
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
