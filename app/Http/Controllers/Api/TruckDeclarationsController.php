<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use App\Models\TruckAssignment;
use App\Models\User;
use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\PostingApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public REST endpoint consumed by wttsystem.dk.
 *
 * Flow: external system logs in by truck plate, resolves the driver name via MAPON,
 * then hits this endpoint. We find which org owns the plate (local `trucks` table),
 * switch to that org's RTPD credentials, match the driver by name, fetch their active
 * SUBMITTED declarations, generate English PDF URLs via POST /declarations/{id}/print.
 */
class TruckDeclarationsController extends Controller
{
    public function __construct(
        protected PostingApiService $apiService,
        protected DriverService $driverService,
        protected DeclarationService $declarationService,
    ) {}

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate' => 'required|string|max:32',
            'driver_name' => 'required|string|max:255',
        ]);

        $plate = strtoupper(preg_replace('/\s+/', '', $validated['plate']));
        $driverName = trim($validated['driver_name']);
        $today = Carbon::today()->format('Y-m-d');

        // 1. Resolve plate → candidate companies. A plate can be registered in
        //    multiple companies' local `trucks` tables (subcontracting, truck
        //    transferred between orgs, etc.), so we try each until one yields
        //    this driver + active declaration.
        $trucks = Truck::whereRaw('UPPER(REPLACE(plate, " ", "")) = ?', [$plate])->get();
        if ($trucks->isEmpty()) {
            return response()->json([
                'error' => 'plate_not_found',
                'message' => "No truck with plate {$plate} is registered in any company.",
            ], 404);
        }

        $attempts = [];           // per-company diagnostic trail, for both logs and 404 message
        $checkedOwners = [];      // names of companies we actually tried (had valid creds)

        foreach ($trucks as $truck) {
            $owner = User::find($truck->user_id);
            if (!$owner || !$owner->hasValidApiCredentials()) {
                $attempts[] = ['owner' => $owner?->name, 'reason' => 'no_credentials'];
                continue;
            }
            $checkedOwners[] = $owner->name;

            // Switch the shared PostingApiService singleton to this owning org.
            $this->apiService->setUserCredentials(
                $owner->api_base_url,
                $owner->api_key,
                $owner->api_operator_id,
            );

            try {
                $driver = $this->findDriverByName($driverName);
            } catch (\Throwable $e) {
                Log::warning('Truck declarations lookup — driver fetch failed', [
                    'plate' => $plate,
                    'owner_user_id' => $owner->id,
                    'error' => $e->getMessage(),
                ]);
                $attempts[] = ['owner' => $owner->name, 'reason' => 'driver_fetch_failed', 'error' => $e->getMessage()];
                continue;
            }

            if (!$driver) {
                $attempts[] = ['owner' => $owner->name, 'reason' => 'driver_not_in_roster'];
                continue;
            }

            $driverId = $driver['driverId'];

            // Gate — accept via EITHER current assignment OR plate-in-active-declaration.
            $assignedNow = TruckAssignment::where('truck_id', $truck->id)
                ->where('driver_id', $driverId)
                ->where('is_active', true)
                ->exists();

            try {
                $active = $this->fetchActiveDeclarationsForDriver($driver, $today);
            } catch (\Throwable $e) {
                Log::warning('Truck declarations lookup — declarations fetch failed', [
                    'driver_id' => $driverId,
                    'owner' => $owner->name,
                    'error' => $e->getMessage(),
                ]);
                $attempts[] = ['owner' => $owner->name, 'reason' => 'declarations_fetch_failed', 'error' => $e->getMessage()];
                continue;
            }

            $matchedVia = null;
            if ($assignedNow) {
                $matchedVia = 'current_assignment';
            } elseif ($this->anyActiveDeclarationCoversPlate($active, $plate)) {
                $matchedVia = 'declaration_plate';
            }

            if ($matchedVia === null) {
                $attempts[] = [
                    'owner' => $owner->name,
                    'reason' => 'driver_not_assigned_to_truck',
                    'active_submitted' => count($active),
                ];
                continue;
            }

            // Success — build the response in this org's context.
            $ids = array_map(fn ($d) => $d['declarationId'], $active);
            $printResults = $this->declarationService->printDeclarationsConcurrent($ids, 'en');

            $declarations = [];
            foreach ($active as $d) {
                $id = $d['declarationId'];
                $declarations[] = [
                    'declarationId' => $id,
                    'country' => strtoupper($d['declarationPostingCountry'] ?? ''),
                    'startDate' => $d['declarationStartDate'] ?? null,
                    'endDate' => $d['declarationEndDate'] ?? null,
                    'pdfUrl' => $printResults[$id]['url'] ?? null,
                ];
            }
            usort($declarations, fn ($a, $b) => strcmp($a['country'], $b['country']));

            Log::info('TruckDeclarations: matched', [
                'plate' => $plate,
                'owner' => $owner->name,
                'matched_via' => $matchedVia,
                'skipped_attempts' => $attempts,
            ]);

            return response()->json([
                'company' => $owner->name,
                'truck' => ['plate' => $truck->plate],
                'driver' => [
                    'driverId' => $driverId,
                    'name' => trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')),
                    'dateOfBirth' => $driver['driverDateOfBirth'] ?? null,
                ],
                'assigned_now' => $assignedNow,
                'matched_via' => $matchedVia,
                'declarations' => $declarations,
            ]);
        }

        // Fell through — no company matched.
        if (empty($checkedOwners)) {
            return response()->json([
                'error' => 'owner_misconfigured',
                'message' => "Plate {$plate} is registered but no owning company has valid IMI API credentials.",
            ], 500);
        }

        // Pick a 4xx code from the attempts. If every attempt was "not in roster"
        // we return driver_not_found; otherwise driver_not_assigned_to_truck.
        $allDriverNotInRoster = !empty($attempts) && collect($attempts)
            ->every(fn ($a) => ($a['reason'] ?? null) === 'driver_not_in_roster');

        Log::info('TruckDeclarations: no match across all owning companies', [
            'plate' => $plate,
            'driver' => $driverName,
            'attempts' => $attempts,
        ]);

        if ($allDriverNotInRoster) {
            return response()->json([
                'error' => 'driver_not_found',
                'message' => "No driver named \"{$driverName}\" found in any company registered for {$plate}.",
                'companies_checked' => $checkedOwners,
            ], 404);
        }

        return response()->json([
            'error' => 'driver_not_assigned_to_truck',
            'message' => "Driver \"{$driverName}\" is not currently assigned to {$plate} and has no active declaration listing it in any company.",
            'companies_checked' => $checkedOwners,
        ], 404);
    }

    private function normalisePlate(string $plate): string
    {
        return strtoupper(preg_replace('/\s+/', '', $plate));
    }

    /**
     * True if any of the driver's active SUBMITTED declarations lists $plate among
     * its vehicle plates (passenger / light / heavy).
     *
     * IMPORTANT: the /declarations LIST response does NOT include plate arrays —
     * those live only on the per-declaration /declarations/{id} DETAIL response.
     * Serial fetches of 20+ declarations exceeded the 30s WTT timeout, so we fire
     * them concurrently via Guzzle Pool (default concurrency 10). Worst-case total
     * latency ≈ max single-call latency, not sum.
     */
    private function anyActiveDeclarationCoversPlate(array $activeDeclarations, string $plate): bool
    {
        $target = $this->normalisePlate($plate);
        if ($target === '') return false;

        $ids = array_values(array_filter(array_map(fn ($d) => $d['declarationId'] ?? null, $activeDeclarations)));
        if (empty($ids)) {
            Log::info('TruckDeclarations plate check: no active declarations to check', ['plate' => $plate]);
            return false;
        }

        $details = $this->declarationService->getDeclarationsConcurrent($ids);

        // DIAG: summarise plate counts per declaration + whether target appears.
        $summary = [];
        $found = false;
        foreach ($details as $id => $detail) {
            if (!is_array($detail)) {
                $summary[$id] = 'fetch_failed';
                continue;
            }
            $allPlates = [];
            foreach (['declarationVehiclePlateNumber', 'declarationVehiclePlateNumberLight', 'declarationVehiclePlateNumberHeavy'] as $field) {
                foreach ((array) ($detail[$field] ?? []) as $p) {
                    $allPlates[] = (string) $p;
                    if ($this->normalisePlate((string) $p) === $target) {
                        $found = true;
                    }
                }
            }
            $summary[$id] = [
                'country' => $detail['declarationPostingCountry'] ?? null,
                'plate_count' => count($allPlates),
                'first_plates' => array_slice($allPlates, 0, 5),
                'contains_target' => in_array($target, array_map(fn ($p) => $this->normalisePlate($p), $allPlates), true),
            ];
        }

        Log::info('TruckDeclarations plate check', [
            'target_plate_normalised' => $target,
            'checked_declarations' => count($ids),
            'found' => $found,
            'summary' => $summary,
        ]);

        return $found;
    }

    private function normaliseName(string $name): string
    {
        $name = trim($name);
        if ($name === '') return '';
        $prev = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'en_US.UTF-8', 'C.UTF-8', 'C');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        setlocale(LC_CTYPE, $prev);
        if ($ascii === false || $ascii === null) $ascii = $name;
        $ascii = preg_replace('/[-_.]/', ' ', $ascii);
        $ascii = preg_replace('/[^A-Za-z\s]/', '', $ascii);
        $ascii = preg_replace('/\s+/', ' ', $ascii);
        return strtolower(trim($ascii));
    }

    /**
     * Paginate /drivers and return the first match for the given full name.
     * Normalises BOTH sides via normaliseName() (strip accents incl. ø→o, æ→ae, å→a;
     * lowercase; collapse whitespace), so e.g. "Christian hjortshøj Lauritzen" and
     * "Christian Hjortshøj  Lauritzen" (double space in stored record) both match.
     */
    private function findDriverByName(string $fullName): ?array
    {
        $needle = $this->normaliseName($fullName);
        if ($needle === '') return null;

        $page = 0;
        $totalScanned = 0;
        $sampleCandidates = [];
        $startKey = null;
        do {
            $page++;
            $pageStart = microtime(true);
            $batch = $this->driverService->getDriversPaginated(250, $startKey);
            Log::info('TruckDeclarations findDriver: page fetched', [
                'page' => $page,
                'ms' => round((microtime(true) - $pageStart) * 1000),
                'items' => count($batch['items'] ?? []),
                'has_next' => !empty($batch['lastEvaluatedKey']),
            ]);
            foreach ($batch['items'] ?? [] as $d) {
                $totalScanned++;
                $candidate = $this->normaliseName(
                    ($d['driverLatinFirstName'] ?? '') . ' ' . ($d['driverLatinLastName'] ?? '')
                );
                if (count($sampleCandidates) < 5) {
                    $sampleCandidates[] = [
                        'raw' => trim(($d['driverLatinFirstName'] ?? '') . ' ' . ($d['driverLatinLastName'] ?? '')),
                        'normalised' => $candidate,
                    ];
                }
                if ($candidate !== '' && $candidate === $needle) {
                    Log::info('TruckDeclarations findDriver: matched', [
                        'driver_id' => $d['driverId'] ?? null,
                        'needle' => $needle,
                        'page' => $page,
                        'total_scanned' => $totalScanned,
                    ]);
                    return $d;
                }
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);

        Log::info('TruckDeclarations findDriver: NO MATCH', [
            'needle' => $needle,
            'pages' => $page,
            'total_scanned' => $totalScanned,
            'sample_candidates' => $sampleCandidates,
        ]);
        return null;
    }

    /**
     * Paginate /declarations (NO driverId filter — the RTPD filter is unreliable for
     * this use), match by driverLatinFullName + driverDateOfBirth like the driver view
     * page does, and keep only active SUBMITTED ones.
     */
    private function fetchActiveDeclarationsForDriver(array $driver, string $today): array
    {
        $driverFullName = $this->normaliseName(
            ($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')
        );
        $driverDob = $driver['driverDateOfBirth'] ?? null;

        // DIAG: first 3 decl records we scan (what the LIST actually gives us for
        // name/dob), and every record whose normalised name matched regardless of
        // status/date.
        $sampleScanned = [];
        $byName = [];

        $active = [];
        $totalScanned = 0;
        $startKey = null;
        do {
            $batch = $this->declarationService->getDeclarationsPaginated(250, $startKey);
            foreach ($batch['items'] ?? [] as $d) {
                $totalScanned++;
                if (count($sampleScanned) < 3) {
                    $sampleScanned[] = [
                        'raw_name' => $d['driverLatinFullName'] ?? null,
                        'normalised' => $this->normaliseName($d['driverLatinFullName'] ?? ''),
                        'dob' => $d['driverDateOfBirth'] ?? null,
                    ];
                }
                if ($this->normaliseName($d['driverLatinFullName'] ?? '') !== $driverFullName) continue;
                if ($driverDob && !empty($d['driverDateOfBirth']) && $d['driverDateOfBirth'] !== $driverDob) continue;

                $byName[] = [
                    'id' => $d['declarationId'] ?? null,
                    'status' => $d['declarationStatus'] ?? null,
                    'end' => $d['declarationEndDate'] ?? null,
                    'country' => $d['declarationPostingCountry'] ?? null,
                ];

                $status = strtoupper($d['declarationStatus'] ?? '');
                $endDate = $d['declarationEndDate'] ?? '';
                if ($status === 'SUBMITTED' && $endDate >= $today) {
                    $active[] = $d;
                }
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);

        Log::info('TruckDeclarations: fetched declarations for driver', [
            'driver_firstlast_raw' => trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')),
            'driver_fullname_normalised' => $driverFullName,
            'driver_dob' => $driverDob,
            'today' => $today,
            'total_scanned' => $totalScanned,
            'matched_by_name_dob' => count($byName),
            'active_submitted' => count($active),
            'sample_scanned_records' => $sampleScanned,
            'name_matches' => $byName,
        ]);

        return $active;
    }
}
