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
        $data = $this->get('driver/list');
        // Known shapes: { data: { units: [{driver: {...}}, ...] } } or { data: { drivers: [...] } }
        $data = $data['data'] ?? $data;
        if (isset($data['drivers']) && is_array($data['drivers'])) {
            return $data['drivers'];
        }
        if (isset($data['units']) && is_array($data['units'])) {
            // Flatten units[].driver into a drivers array
            $out = [];
            foreach ($data['units'] as $unit) {
                if (!empty($unit['driver'])) $out[] = $unit['driver'];
            }
            return $out;
        }
        return [];
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

        // Common shape 1: { data: { days: [ { date: 'YYYY-MM-DD', intervals: [...] }, ... ] } }
        $days = $data['days'] ?? ($data[0]['days'] ?? null);
        $latest = null;

        if (is_array($days)) {
            foreach ($days as $day) {
                $date = $day['date'] ?? null;
                if (!$date) continue;
                // Only count the day if it has any intervals (any activity).
                $hasAny = !empty($day['intervals']) || !empty($day['activities']);
                if ($hasAny && ($latest === null || $date > $latest)) {
                    $latest = $date;
                }
            }
            return $latest;
        }

        // Fallback: flat intervals list with timestamps.
        $intervals = $data['intervals'] ?? [];
        foreach ($intervals as $iv) {
            $date = isset($iv['start']) ? substr($iv['start'], 0, 10) : null;
            if ($date && ($latest === null || $date > $latest)) {
                $latest = $date;
            }
        }
        return $latest;
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
