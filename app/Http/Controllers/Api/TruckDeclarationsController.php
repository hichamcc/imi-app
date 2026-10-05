<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Truck;
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

        // 4. Fetch active SUBMITTED declarations for this driver (end_date >= today).
        $driverId = $driver['driverId'];
        $today = Carbon::today()->format('Y-m-d');

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
            'declarations' => $declarations,
        ]);
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
