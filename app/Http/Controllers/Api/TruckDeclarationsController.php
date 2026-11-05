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
        $t0 = microtime(true);
        $tick = function (string $label) use (&$t0) {
            $elapsed = round((microtime(true) - $t0) * 1000);
            Log::info("TruckDeclarations TIMING {$label}", ['ms_since_start' => $elapsed]);
        };

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

        $tick('after_set_credentials');

        // 3. Find the driver by name (paginate /drivers, exact match on first+last).
        try {
            $driver = $this->findDriverByName($driverName);
            $tick('after_find_driver');
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

        // 3b. Gate — accept the driver via either of two paths, in order:
        //   1) currently assigned to this truck (TruckAssignment.is_active = true)
        //   2) has an active SUBMITTED declaration that lists this plate
        // Rejects anyone else even if they're in the owner's IMI roster.
        $assignedNow = TruckAssignment::where('truck_id', $truck->id)
            ->where('driver_id', $driverId)
            ->where('is_active', true)
            ->exists();

        $matchedVia = $assignedNow ? 'current_assignment' : null;

        // Fetch active SUBMITTED declarations (needed for both the plate-in-decl
        // fallback and the success response body).
        try {
            $active = $this->fetchActiveDeclarationsForDriver($driver, $today);
            $tick('after_fetch_active_declarations');
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

        // The /declarations LIST response doesn't include plate arrays — only the
        // per-declaration /declarations/{id} DETAIL does. So for the plate-match gate
        // we fetch details on demand, stopping at the first match.
        if ($matchedVia === null && $this->anyActiveDeclarationCoversPlate($active, $plate)) {
            $matchedVia = 'declaration_plate';
        }
        $tick('after_plate_check');

        if ($matchedVia === null) {
            return response()->json([
                'error' => 'driver_not_assigned_to_truck',
                'message' => "Driver \"{$driverName}\" is not currently assigned to {$truck->plate} and has no active declaration listing it.",
                'company' => $owner->name,
            ], 404);
        }

        // 5. Generate English PDF URL per declaration (RTPD /print returns a signed URL).
        //    Done concurrently via Guzzle Pool — serial was the main cause of the 30s
        //    timeout when a driver had 20+ active declarations.
        $ids = array_map(fn ($d) => $d['declarationId'], $active);
        $printResults = $this->declarationService->printDeclarationsConcurrent($ids, 'en');
        $tick('after_print_urls');

        $declarations = [];
        foreach ($active as $d) {
            $id = $d['declarationId'];
            $pdfUrl = $printResults[$id]['url'] ?? null;

            $declarations[] = [
                'declarationId' => $id,
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
            'assigned_now' => $assignedNow,       // true: current assignment; false: matched via declaration plate list
            'matched_via' => $matchedVia,         // current_assignment | declaration_plate
            'declarations' => $declarations,
        ]);
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
        if (empty($ids)) return false;

        $details = $this->declarationService->getDeclarationsConcurrent($ids);

        foreach ($details as $detail) {
            if (!is_array($detail)) continue;
            foreach (['declarationVehiclePlateNumber', 'declarationVehiclePlateNumberLight', 'declarationVehiclePlateNumberHeavy'] as $field) {
                foreach ((array) ($detail[$field] ?? []) as $p) {
                    if ($this->normalisePlate((string) $p) === $target) {
                        return true;
                    }
                }
            }
        }
        return false;
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

        $startKey = null;
        do {
            $batch = $this->driverService->getDriversPaginated(250, $startKey);
            foreach ($batch['items'] ?? [] as $d) {
                $candidate = $this->normaliseName(
                    ($d['driverLatinFirstName'] ?? '') . ' ' . ($d['driverLatinLastName'] ?? '')
                );
                if ($candidate !== '' && $candidate === $needle) {
                    return $d;
                }
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return null;
    }

    /**
     * Paginate /declarations (NO driverId filter — the RTPD filter is unreliable for
     * this use), match by driverLatinFullName + driverDateOfBirth like the driver view
     * page does, and keep only active SUBMITTED ones.
     */
    private function fetchActiveDeclarationsForDriver(array $driver, string $today): array
    {
        $driverFullName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
        $driverDob = $driver['driverDateOfBirth'] ?? null;

        $active = [];
        $startKey = null;
        do {
            $batch = $this->declarationService->getDeclarationsPaginated(250, $startKey);
            foreach ($batch['items'] ?? [] as $d) {
                if (($d['driverLatinFullName'] ?? null) !== $driverFullName) continue;
                if ($driverDob && !empty($d['driverDateOfBirth']) && $d['driverDateOfBirth'] !== $driverDob) continue;

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
