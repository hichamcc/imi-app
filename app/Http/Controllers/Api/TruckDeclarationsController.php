<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use App\Models\TruckAssignment;
use App\Models\User;
use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\MaponService;
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
        protected MaponService $maponService,
    ) {}

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate' => 'required|string|max:32',
            'driver_name' => 'required|string|max:255',
        ]);

        $plate = strtoupper(preg_replace('/\s+/', '', $validated['plate']));
        $driverName = trim($validated['driver_name']);

        // 1. Resolve plate → company (owning user).
        $truck = Truck::whereRaw('UPPER(REPLACE(plate, " ", "")) = ?', [$plate])->first();
        if (!$truck) {
            return response()->json([
                'error' => 'plate_not_found',
                'message' => "No truck with plate {$plate} is registered in any company.",
            ], 404);
        }

        $owner = User::find($truck->user_id);
        if (!$owner || !$owner->hasValidApiCredentials()) {
            return response()->json([
                'error' => 'owner_misconfigured',
                'message' => 'The owning company has no valid IMI API credentials.',
            ], 500);
        }

        // 2. Switch the shared PostingApiService singleton to the owning org.
        $this->apiService->setUserCredentials(
            $owner->api_base_url,
            $owner->api_key,
            $owner->api_operator_id,
        );

        // 3. Find the driver by name (paginate /drivers, exact match on first+last).
        try {
            $driver = $this->findDriverByName($driverName);
        } catch (\Throwable $e) {
            Log::warning('Truck declarations lookup — driver fetch failed', [
                'plate' => $plate,
                'owner_user_id' => $owner->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'driver_lookup_failed',
                'message' => $e->getMessage(),
            ], 502);
        }

        if (!$driver) {
            return response()->json([
                'error' => 'driver_not_found',
                'message' => "No driver named \"{$driverName}\" found in company \"{$owner->name}\".",
                'company' => $owner->name,
            ], 404);
        }

        $driverId = $driver['driverId'];
        $today = Carbon::today()->format('Y-m-d');

        // 3b. Gate: driver must be CURRENTLY assigned to this truck, OR have driven
        //     this exact truck earlier today according to MAPON tacho data. Rejects
        //     anyone else even if they're in the owner's IMI roster.
        $assignedNow = TruckAssignment::where('truck_id', $truck->id)
            ->where('driver_id', $driverId)
            ->where('is_active', true)
            ->exists();

        $assignedToday = $assignedNow;
        if (!$assignedNow) {
            $assignedToday = $this->droveTruckToday($driverName, $truck->plate);
        }

        if (!$assignedToday) {
            return response()->json([
                'error' => 'driver_not_found',
                'message' => "Driver \"{$driverName}\" is not currently assigned to {$truck->plate} and did not drive it today.",
                'company' => $owner->name,
            ], 404);
        }

        // 4. Fetch active SUBMITTED declarations for this driver (end_date >= today).

        try {
            $active = $this->fetchActiveDeclarationsForDriver($driverId, $today);
        } catch (\Throwable $e) {
            Log::warning('Truck declarations lookup — declarations fetch failed', [
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'declarations_fetch_failed',
                'message' => $e->getMessage(),
            ], 502);
        }

        // 5. Generate English PDF URL per declaration (RTPD /print returns a signed URL).
        $declarations = [];
        foreach ($active as $d) {
            $pdfUrl = null;
            try {
                $printResult = $this->declarationService->printDeclaration($d['declarationId'], 'en');
                $pdfUrl = $printResult['url'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('Truck declarations lookup — print URL failed', [
                    'declaration_id' => $d['declarationId'],
                    'error' => $e->getMessage(),
                ]);
            }

            $declarations[] = [
                'declarationId' => $d['declarationId'],
                'country' => strtoupper($d['declarationPostingCountry'] ?? ''),
                'startDate' => $d['declarationStartDate'] ?? null,
                'endDate' => $d['declarationEndDate'] ?? null,
                'pdfUrl' => $pdfUrl,
            ];
        }

        // Sort by country for consistent output
        usort($declarations, fn($a, $b) => strcmp($a['country'], $b['country']));

        return response()->json([
            'company' => $owner->name,
            'truck' => ['plate' => $truck->plate],
            'driver' => [
                'driverId' => $driverId,
                'name' => trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')),
                'dateOfBirth' => $driver['driverDateOfBirth'] ?? null,
            ],
            'assigned_now' => $assignedNow, // true: current assignment; false: matched via today's MAPON activity
            'declarations' => $declarations,
        ]);
    }

    /**
     * MAPON-based fallback: did $driverName have any DRIVING activity today on the
     * MAPON unit whose plate matches $plate? Resolves plate → MAPON unitId via
     * unit/list, driver name → MAPON driverId via driver/list (same fuzzy matcher
     * the Tacho Activity report uses), then calls drivingUnitIdsToday.
     * Returns false on any lookup failure — fail-closed, not fail-open.
     */
    private function droveTruckToday(string $driverName, string $plate): bool
    {
        if (!$this->maponService->isConfigured()) return false;

        try {
            // 1. Plate → MAPON unitId. Normalise plate (strip spaces, uppercase) on both sides.
            $target = $this->normalisePlate($plate);
            $unitId = null;
            foreach ($this->maponService->listUnits() as $unit) {
                $cand = $this->normalisePlate((string) ($unit['number'] ?? $unit['plate_number'] ?? $unit['plate'] ?? ''));
                if ($cand === '' || $cand !== $target) continue;
                $unitId = $unit['unit_id'] ?? $unit['id'] ?? null;
                if ($unitId !== null) break;
            }
            if ($unitId === null) {
                Log::info('MAPON fallback: plate not found in unit/list', ['plate' => $plate]);
                return false;
            }

            // 2. Driver name → MAPON driver id. Same matcher as the admin Tacho report:
            //    normalise (strip diacritics, lowercase, collapse whitespace), then
            //    try exact then subset-of-tokens.
            $needle = $this->normaliseName($driverName);
            $needleTokens = array_filter(explode(' ', $needle));
            $maponDriverId = null;
            foreach ($this->maponService->listDrivers() as $m) {
                $first = $m['name'] ?? $m['first_name'] ?? $m['firstname'] ?? '';
                $last = $m['surname'] ?? $m['last_name'] ?? $m['lastname'] ?? '';
                $cand = $this->normaliseName(trim("{$first} {$last}"));
                if ($cand === '') continue;

                if ($cand === $needle) {
                    $maponDriverId = $m['id'] ?? $m['driver_id'] ?? null;
                    if ($maponDriverId !== null) break;
                }
                if ($maponDriverId === null && count($needleTokens) >= 2) {
                    $candTokens = array_filter(explode(' ', $cand));
                    if (empty(array_diff($needleTokens, $candTokens))) {
                        $maponDriverId = $m['id'] ?? $m['driver_id'] ?? null;
                    }
                }
            }
            if ($maponDriverId === null) {
                Log::info('MAPON fallback: driver name not found', ['driver_name' => $driverName]);
                return false;
            }

            // 3. Any DRIVING activity today on that unit?
            $drivenUnitIds = $this->maponService->drivingUnitIdsToday($maponDriverId);
            return in_array((string) $unitId, $drivenUnitIds, true);
        } catch (\Throwable $e) {
            Log::warning('MAPON fallback failed', ['driver_name' => $driverName, 'plate' => $plate, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalisePlate(string $plate): string
    {
        return strtoupper(preg_replace('/\s+/', '', $plate));
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
     * Case/whitespace-insensitive on `firstName + lastName`.
     */
    private function findDriverByName(string $fullName): ?array
    {
        $needle = strtolower(trim(preg_replace('/\s+/', ' ', $fullName)));
        $startKey = null;
        do {
            $batch = $this->driverService->getDriversPaginated(250, $startKey);
            foreach ($batch['items'] ?? [] as $d) {
                $candidate = strtolower(trim(($d['driverLatinFirstName'] ?? '') . ' ' . ($d['driverLatinLastName'] ?? '')));
                if ($candidate === $needle) {
                    return $d;
                }
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return null;
    }

    /**
     * Paginate /declarations filtered by driverId and keep only active SUBMITTED ones.
     */
    private function fetchActiveDeclarationsForDriver(string $driverId, string $today): array
    {
        $active = [];
        $startKey = null;
        do {
            $batch = $this->declarationService->getDeclarationsPaginated(250, $startKey, ['driverId' => $driverId]);
            foreach ($batch['items'] ?? [] as $d) {
                $status = strtoupper($d['declarationStatus'] ?? '');
                $endDate = $d['declarationEndDate'] ?? '';
                if ($status === 'SUBMITTED' && $endDate >= $today) {
                    $active[] = $d;
                }
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return $active;
    }
}
