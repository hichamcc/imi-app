<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Looks up which IMI company a person belongs to across all organisations
 * the current user can impersonate (plus their own).
 *
 * Strategy:
 *   - Per (auth user) we cache a flat array of every (name, dob, driverId, companyName, companyUserId)
 *     from every impersonatable org. One API call per org per cache window.
 *   - Lookups are local (in-memory) after that.
 */
class ImiPresenceLookup
{
    private const CACHE_TTL = 600; // 10 minutes
    private const CACHE_KEY_PREFIX = 'imi_presence_v1';

    public function __construct(
        protected PostingApiService $apiService,
        protected DriverService $driverService,
    ) {}

    private function cacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . ":user:{$userId}";
    }

    public function bust(?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        Cache::forget($this->cacheKey($userId));
    }

    /**
     * Returns the cached name+DOB index. Format:
     *   [ "first last|YYYY-MM-DD" => [ ['driver_id'=>..., 'company_user_id'=>..., 'company_name'=>...], ... ] ]
     * DOB is optional — entries are also indexed by name-only ("first last|").
     */
    public function getIndex(): array
    {
        $userId = auth()->id();
        if (!$userId) return [];

        return Cache::remember($this->cacheKey($userId), self::CACHE_TTL, function () {
            return $this->buildIndex();
        });
    }

    private function buildIndex(): array
    {
        $authUser = auth()->user();
        if (!$authUser) return [];

        // Source credentials so we can restore at the end
        $sourceBase = $authUser->api_base_url;
        $sourceKey = $authUser->api_key;
        $sourceOp = $authUser->api_operator_id;

        // All orgs to scan: current user + everyone they can impersonate
        $orgs = collect([$authUser])->merge($authUser->getImpersonatableUsers())
            ->unique('id')
            ->filter(fn ($u) => $u->hasValidApiCredentials())
            ->values();

        $index = [];

        foreach ($orgs as $org) {
            try {
                $this->apiService->setUserCredentials($org->api_base_url, $org->api_key, $org->api_operator_id);

                // Walk pagination
                $startKey = null;
                do {
                    $params = ['limit' => 250];
                    if ($startKey) $params['startKey'] = $startKey;

                    $page = $this->driverService->getDrivers($params);
                    $items = $page['items'] ?? $page ?? [];
                    $startKey = $page['lastEvaluatedKey'] ?? null;

                    foreach ($items as $d) {
                        $first = trim($d['driverLatinFirstName'] ?? '');
                        $last = trim($d['driverLatinLastName'] ?? '');
                        if ($first === '' && $last === '') continue;

                        $name = strtolower(trim("$first $last"));
                        $dob = $d['driverDateOfBirth'] ?? '';

                        $entry = [
                            'driver_id' => $d['driverId'] ?? null,
                            'company_user_id' => $org->id,
                            'company_name' => $org->name,
                            'first_name' => $first,
                            'last_name' => $last,
                            'date_of_birth' => $dob ?: null,
                        ];

                        // Index by name+dob and by name-only
                        $index["$name|$dob"][] = $entry;
                        if ($dob !== '') {
                            $index["$name|"][] = $entry;
                        }
                    }
                } while ($startKey);
            } catch (\Throwable $e) {
                \Log::warning('ImiPresenceLookup: org fetch failed', [
                    'user_id' => $org->id, 'org' => $org->name, 'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Restore source credentials
        if ($sourceBase && $sourceKey && $sourceOp) {
            $this->apiService->setUserCredentials($sourceBase, $sourceKey, $sourceOp);
        }

        return $index;
    }

    /**
     * Find IMI matches for a given person. Tries name+DOB first, falls back to name only.
     * Returns an array of entries (may be empty).
     */
    public function findForName(string $firstName, string $lastName, ?string $dob = null): array
    {
        $idx = $this->getIndex();
        $name = strtolower(trim("$firstName $lastName"));

        if ($dob && isset($idx["$name|$dob"])) {
            return $idx["$name|$dob"];
        }
        return $idx["$name|"] ?? [];
    }
}
