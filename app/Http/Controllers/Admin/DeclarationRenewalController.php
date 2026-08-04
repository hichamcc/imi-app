<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\User;
use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\PlateNumberService;
use App\Services\PostingApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TEMPORARY admin tool to catch up drivers whose auto-renewal missed the RTPD API change.
 * Lists drivers that:
 *   - Have auto-renew enabled
 *   - Have <= 1 active SUBMITTED declaration
 *   - Have at least one EXPIRED declaration since the cutoff date (default 2026-06-22)
 * Provides a per-driver "Renew all" action that uses the /plate-numbers register to
 * split legacy plates into LIGHT/HEAVY and drops any that aren't registered.
 */
class DeclarationRenewalController extends Controller
{
    public function __construct(
        protected DriverService $driverService,
        protected DeclarationService $declarationService,
        protected PlateNumberService $plateNumberService,
        protected PostingApiService $apiService,
    ) {}

    private const CUTOFF_DATE = '2026-06-22';

    public function index(Request $request)
    {
        $apiUsers = $this->credentialedUsers();
        $selectedUserId = $request->integer('user_id') ?: (auth()->user()->hasValidApiCredentials() ? auth()->id() : ($apiUsers->first()->id ?? null));

        $data = ['drivers' => [], 'totalDrivers' => 0, 'apiError' => null];
        if ($selectedUserId) {
            $this->useUserCredentials($selectedUserId);
            $data = $this->buildDriverReport();
        }

        return view('admin.declaration-renewals.index', [
            'drivers' => $data['drivers'],
            'cutoff' => self::CUTOFF_DATE,
            'totalDrivers' => $data['totalDrivers'],
            'apiError' => $data['apiError'] ?? null,
            'apiUsers' => $apiUsers,
            'selectedUserId' => $selectedUserId,
        ]);
    }

    /**
     * Renew all expired-since-cutoff countries for a single driver, skipping any
     * country that already has an active SUBMITTED declaration.
     */
    public function renewDriver(Request $request, string $driverId)
    {
        try {
            $userId = $request->integer('user_id') ?: (auth()->user()->hasValidApiCredentials() ? auth()->id() : null);
            if (!$userId) {
                return redirect()->route('admin.declaration-renewals.index')
                    ->with('error', 'No API-credentialed user selected.');
            }
            $this->useUserCredentials($userId);

            $today = Carbon::today()->format('Y-m-d');
            $plateMap = $this->buildPlateWeightMap();

            $allDrivers = $this->fetchAllDrivers();
            $allDeclarations = $this->fetchAllDeclarations();

            // Same name → driver map used by the index page to associate declarations
            // (which lack driverId in the list response) to their driver.
            $nameToDrivers = [];
            foreach ($allDrivers as $driver) {
                $key = strtolower(trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')));
                if ($key !== '') $nameToDrivers[$key][] = $driver;
            }

            // Countries this driver already has an active SUBMITTED declaration for.
            $activeCountries = [];
            foreach ($allDeclarations as $d) {
                if ($this->resolveDeclarationDriverId($d, $nameToDrivers) !== $driverId) continue;
                if (strtoupper($d['declarationStatus'] ?? '') !== 'SUBMITTED') continue;
                if (($d['declarationEndDate'] ?? '') < $today) continue;
                $activeCountries[strtoupper($d['declarationPostingCountry'] ?? '')] = true;
            }

            // Candidates: dedupe by country, keep the most recent expired end_date.
            $candidates = [];
            foreach ($allDeclarations as $d) {
                if ($this->resolveDeclarationDriverId($d, $nameToDrivers) !== $driverId) continue;
                $endDate = $d['declarationEndDate'] ?? '';
                if ($endDate < self::CUTOFF_DATE || $endDate >= $today) continue;
                $country = strtoupper($d['declarationPostingCountry'] ?? '');
                if (isset($activeCountries[$country])) continue;
                if (!isset($candidates[$country]) || $endDate > ($candidates[$country]['declarationEndDate'] ?? '')) {
                    $candidates[$country] = $d;
                }
            }

            if (empty($candidates)) {
                return redirect()->route('admin.declaration-renewals.index')
                    ->with('info', 'Nothing to renew for this driver — every expired country is either already covered or out of range.');
            }

            $created = 0;
            $errors = [];
            $skippedNoPlates = 0;

            foreach ($candidates as $country => $summary) {
                try {
                    $original = $this->declarationService->getDeclaration($summary['declarationId']);
                    $newData = $this->prepareRenewalPayload($original, $plateMap, $droppedPlates);

                    if ($newData === null) {
                        $skippedNoPlates++;
                        continue;
                    }

                    $result = $this->declarationService->createDeclaration($newData);
                    $newId = $result['declarationId'] ?? null;
                    if (!$newId) {
                        $errors[] = "{$country}: API did not return a declarationId";
                        continue;
                    }
                    $this->declarationService->submitDeclaration($newId);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = "{$country}: " . $e->getMessage();
                    Log::warning('Admin renew failed for driver', [
                        'driver_id' => $driverId,
                        'country' => $country,
                        'source_declaration_id' => $summary['declarationId'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $msg = "Renewed {$created} declaration(s) for driver.";
            if ($skippedNoPlates > 0) $msg .= " Skipped {$skippedNoPlates} (no registered plates left).";
            if (!empty($errors)) $msg .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '…' : '');

            return redirect()->route('admin.declaration-renewals.index', ['user_id' => $userId])
                ->with(empty($errors) ? 'success' : 'error', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('admin.declaration-renewals.index')
                ->with('error', 'Renewal aborted: ' . $e->getMessage());
        }
    }

    // ---- helpers ----

    /**
     * The /declarations LIST endpoint doesn't include driverId — it only has driverLatinFullName.
     * Resolve to a driverId by looking up the name in the drivers register, tie-breaking on DOB.
     */
    private function resolveDeclarationDriverId(array $declaration, array $nameToDrivers): ?string
    {
        // Some flows may include it directly (e.g. from the FULL GET). Prefer that.
        if (!empty($declaration['driverId'])) return $declaration['driverId'];

        $fullName = trim($declaration['driverLatinFullName'] ?? '');
        if ($fullName === '') return null;
        $key = strtolower($fullName);
        $matches = $nameToDrivers[$key] ?? [];
        if (empty($matches)) return null;
        if (count($matches) === 1) return $matches[0]['driverId'] ?? null;

        // Multiple drivers with the same name — tie-break on DOB when both sides have it.
        $dob = $declaration['driverDateOfBirth'] ?? null;
        if ($dob) {
            foreach ($matches as $d) {
                if (($d['driverDateOfBirth'] ?? null) === $dob) return $d['driverId'] ?? null;
            }
        }
        // Fallback: first match (same fallback DriverService uses).
        return $matches[0]['driverId'] ?? null;
    }

    /**
     * Users that have a full set of RTPD API credentials. Feeds the org selector.
     */
    private function credentialedUsers()
    {
        return User::whereNotNull('api_key')
            ->whereNotNull('api_operator_id')
            ->whereNotNull('api_base_url')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Switch the shared PostingApiService singleton to a specific user's credentials.
     * The other services (DeclarationService, DriverService, PlateNumberService) share
     * this singleton, so they'll all hit that user's org.
     */
    private function useUserCredentials(int $userId): void
    {
        $user = User::findOrFail($userId);
        if (!$user->api_key || !$user->api_operator_id || !$user->api_base_url) {
            throw new \RuntimeException("User {$user->name} has incomplete API credentials.");
        }
        $this->apiService->setUserCredentials($user->api_base_url, $user->api_key, $user->api_operator_id);
    }

    private function buildDriverReport(): array
    {
        try {
            $drivers = $this->fetchAllDrivers();
            $declarations = $this->fetchAllDeclarations();
        } catch (\Throwable $e) {
            return ['drivers' => [], 'totalDrivers' => 0, 'apiError' => $e->getMessage()];
        }

        $today = Carbon::today()->format('Y-m-d');

        // The /declarations LIST response returns driverLatinFullName but no driverId.
        // Build a name → driverId map so we can associate declarations to their driver.
        // If the same name maps to multiple drivers, we tie-break by driverDateOfBirth.
        $nameToDrivers = [];
        foreach ($drivers as $driver) {
            $key = strtolower(trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')));
            if ($key === '') continue;
            $nameToDrivers[$key][] = $driver;
        }

        // driverId => ['active' => [country => true], 'expired' => [country => latest_end_date]]
        $perDriver = [];
        foreach ($declarations as $d) {
            $driverId = $this->resolveDeclarationDriverId($d, $nameToDrivers);
            if (!$driverId) continue;
            $country = strtoupper($d['declarationPostingCountry'] ?? '');
            $status = strtoupper($d['declarationStatus'] ?? '');
            $endDate = $d['declarationEndDate'] ?? '';

            if (!isset($perDriver[$driverId])) {
                $perDriver[$driverId] = ['active' => [], 'expired' => []];
            }

            if ($status === 'SUBMITTED' && $endDate >= $today) {
                $perDriver[$driverId]['active'][$country] = true;
            }
            if ($endDate >= self::CUTOFF_DATE && $endDate < $today) {
                $current = $perDriver[$driverId]['expired'][$country] ?? '';
                if ($endDate > $current) {
                    // Store the source declaration id alongside so renewDriver can find it later
                    $perDriver[$driverId]['expired'][$country] = $endDate;
                }
            }
        }

        $rows = [];
        foreach ($drivers as $driver) {
            $driverId = $driver['driverId'] ?? null;
            if (!$driverId) continue;

            if (!DriverProfile::isAutoRenewEnabled($driverId)) continue;

            $stats = $perDriver[$driverId] ?? ['active' => [], 'expired' => []];
            $activeCount = count($stats['active']);

            // Remove expired countries that are already covered by an active one.
            $renewableExpired = array_diff_key($stats['expired'], $stats['active']);
            if (empty($renewableExpired)) continue;
            if ($activeCount > 1) continue; // <=1 as requested

            $rows[] = [
                'id' => $driverId,
                'name' => trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')),
                'active_countries' => array_keys($stats['active']),
                'active_count' => $activeCount,
                'expired_countries' => $renewableExpired, // country => latest_end_date
            ];
        }

        // Sort by driver name for stable output
        usort($rows, fn($a, $b) => strcmp($a['name'], $b['name']));

        return ['drivers' => $rows, 'totalDrivers' => count($drivers)];
    }

    /**
     * Fetch every declaration for the current user, across all API pages.
     */
    private function fetchAllDeclarations(): array
    {
        $all = [];
        $startKey = null;
        do {
            $batch = $this->declarationService->getDeclarationsPaginated(250, $startKey);
            $all = array_merge($all, $batch['items'] ?? []);
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return $all;
    }

    /**
     * Fetch every driver for the current user, across all API pages.
     */
    private function fetchAllDrivers(): array
    {
        $all = [];
        $startKey = null;
        do {
            $batch = $this->driverService->getDriversPaginated(250, $startKey);
            $all = array_merge($all, $batch['items'] ?? []);
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return $all;
    }

    private function buildPlateWeightMap(): array
    {
        $map = [];
        try {
            foreach ($this->plateNumberService->all() as $p) {
                $plate = $p['plateNumber'] ?? null;
                if (!$plate) continue;
                $type = $p['plateNumberTransportType'] ?? $p['transportType'] ?? null;
                $weight = $p['vehicleWeight'] ?? '';
                if ($type === 'CARRIAGE_OF_PASSENGERS') $map[$plate] = 'PASSENGERS';
                elseif ($type === 'CARRIAGE_OF_GOODS') $map[$plate] = $weight === 'LIGHT' ? 'LIGHT' : 'HEAVY';
            }
        } catch (\Throwable $e) {
            Log::warning('Admin renew: failed to load plate register', ['error' => $e->getMessage()]);
        }
        return $map;
    }

    /**
     * Prepare a renewal payload from a source declaration. Returns null if no registered
     * plates are left after filtering (declaration would be rejected by the API).
     * $droppedPlates is filled by reference for reporting.
     */
    private function prepareRenewalPayload(array $original, array $plateMap, ?array &$droppedPlates = null): ?array
    {
        $droppedPlates = [];
        $today = Carbon::today();
        $originalStart = Carbon::parse($original['declarationStartDate']);
        $originalEnd = Carbon::parse($original['declarationEndDate']);
        $duration = min($originalStart->diffInDays($originalEnd), 180);

        $asTransportManager = $original['otherContactAsTransportManager'] ?? false;
        if (!$asTransportManager) {
            if (empty(trim($original['otherContactFirstName'] ?? '')) || empty(trim($original['otherContactLastName'] ?? ''))) {
                $asTransportManager = true;
            }
        }

        $newData = [
            'declarationPostingCountry' => $original['declarationPostingCountry'],
            'declarationStartDate' => $today->format('Y-m-d'),
            'declarationEndDate' => $today->copy()->addDays($duration)->format('Y-m-d'),
            'declarationOperationType' => $original['declarationOperationType'] ?? ['INTERNATIONAL_CARRIAGE'],
            'declarationTransportType' => $original['declarationTransportType'] ?? ['CARRIAGE_OF_GOODS'],
            'driverId' => $original['driverId'],
            'otherContactAsTransportManager' => $asTransportManager,
        ];
        foreach (['otherContactFirstName', 'otherContactLastName', 'otherContactEmail', 'otherContactPhone'] as $f) {
            if (isset($original[$f])) $newData[$f] = $original[$f];
        }

        $allPlates = array_unique(array_merge(
            $original['declarationVehiclePlateNumber'] ?? [],
            $original['declarationVehiclePlateNumberLight'] ?? [],
            $original['declarationVehiclePlateNumberHeavy'] ?? [],
        ));
        $transportTypes = $newData['declarationTransportType'];

        if (in_array('CARRIAGE_OF_GOODS', $transportTypes, true)) {
            $light = $heavy = [];
            foreach ($allPlates as $plate) {
                $w = $plateMap[$plate] ?? null;
                if ($w === 'LIGHT') $light[] = $plate;
                elseif ($w === 'HEAVY') $heavy[] = $plate;
                elseif ($w === 'PASSENGERS') continue;
                else $droppedPlates[] = $plate;
            }
            if (!empty($light)) $newData['declarationVehiclePlateNumberLight'] = array_values(array_unique($light));
            if (!empty($heavy)) $newData['declarationVehiclePlateNumberHeavy'] = array_values(array_unique($heavy));
        }
        if (in_array('CARRIAGE_OF_PASSENGERS', $transportTypes, true)) {
            $passengers = [];
            foreach ($allPlates as $plate) {
                $w = $plateMap[$plate] ?? null;
                if ($w === 'PASSENGERS') $passengers[] = $plate;
            }
            if (!empty($passengers)) $newData['declarationVehiclePlateNumber'] = array_values(array_unique($passengers));
        }

        $hasAnyPlate = !empty($newData['declarationVehiclePlateNumber'])
            || !empty($newData['declarationVehiclePlateNumberLight'])
            || !empty($newData['declarationVehiclePlateNumberHeavy']);

        return $hasAnyPlate ? $newData : null;
    }
}
