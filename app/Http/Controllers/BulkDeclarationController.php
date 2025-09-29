<?php

namespace App\Http\Controllers;

use App\Services\DeclarationService;
use App\Services\DriverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BulkDeclarationController extends Controller
{
    protected DeclarationService $declarationService;
    protected DriverService $driverService;

    public function __construct(DeclarationService $declarationService, DriverService $driverService)
    {
        $this->declarationService = $declarationService;
        $this->driverService = $driverService;
    }

    /**
     * Show the bulk update form - Step 1: Driver selection
     */
    public function index()
    {
        // Get user's drivers for selection
        $drivers = collect();
        $startKey = null;

        try {
            do {
                $driversBatch = $this->driverService->getDriversPaginated(250, $startKey);
                $currentDrivers = $driversBatch['items'] ?? $driversBatch ?? [];

                if (is_array($currentDrivers)) {
                    $drivers = $drivers->merge($currentDrivers);
                }

                $startKey = $driversBatch['lastEvaluatedKey'] ?? null;
            } while ($startKey);

            // Get submitted declarations count for each driver
            $driversWithStats = $this->getDriversWithDeclarationStats($drivers->toArray());

        } catch (\Exception $e) {
            return redirect()->route('declarations.index')
                ->with('error', 'Failed to load drivers: ' . $e->getMessage());
        }

        return view('declarations.bulk-update.step1-drivers', compact('driversWithStats'));
    }

    /**
     * Handle step 1 submission and show step 2: Action selection
     */
    public function step2(Request $request)
    {
        $request->validate([
            'selected_drivers' => 'required|array|min:1',
            'selected_drivers.*' => 'required|string'
        ]);

        $selectedDriverIds = $request->input('selected_drivers');

        // Get selected drivers data
        $selectedDrivers = [];
        $totalSubmittedDeclarations = 0;

        foreach ($selectedDriverIds as $driverId) {
            try {
                $driver = $this->driverService->getDriver($driverId);
                $submittedCount = $this->getDriverSubmittedDeclarationsCount($driverId);

                $selectedDrivers[] = [
                    'driver' => $driver,
                    'submitted_count' => $submittedCount
                ];

                $totalSubmittedDeclarations += $submittedCount;

            } catch (\Exception $e) {
                Log::warning('Could not load driver for bulk update', [
                    'driver_id' => $driverId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('declarations.bulk-update.step2-action', compact('selectedDrivers', 'totalSubmittedDeclarations'));
    }

    /**
     * Handle step 2 submission and show step 3: Declaration review
     */
    public function step3(Request $request)
    {
        $request->validate([
            'selected_drivers' => 'required|array|min:1',
            'action' => 'required|in:add,remove'
        ]);

        $selectedDriverIds = $request->input('selected_drivers');
        $action = $request->input('action');

        // Get all submitted declarations for selected drivers
        $declarations = $this->getSubmittedDeclarationsForDrivers($selectedDriverIds);

        return view('declarations.bulk-update.step3-declarations', compact('declarations', 'selectedDriverIds', 'action'));
    }

    /**
     * Handle step 3 submission and show step 4: Plate selection
     */
    public function step4(Request $request)
    {
        $request->validate([
            'selected_drivers' => 'required|array|min:1',
            'selected_declarations' => 'required|array|min:1',
            'action' => 'required|in:add,remove'
        ]);

        $selectedDriverIds = $request->input('selected_drivers');
        $selectedDeclarationIds = $request->input('selected_declarations');
        $action = $request->input('action');

        // Get user's trucks for plate selection
        $trucks = auth()->user()->trucks()->where('status', 'available')->get();

        // Get current plates in selected declarations for smart filtering
        $currentPlatesData = $this->getCurrentPlatesInDeclarations($selectedDeclarationIds);

        return view('declarations.bulk-update.step4-plates', compact(
            'trucks', 'selectedDriverIds', 'selectedDeclarationIds', 'action', 'currentPlatesData'
        ));
    }

    /**
     * Handle step 4 submission and show step 5: Preview
     */
    public function step5(Request $request)
    {
        $request->validate([
            'selected_drivers' => 'required|array|min:1',
            'selected_declarations' => 'required|array|min:1',
            'selected_plates' => 'required|array|min:1',
            'action' => 'required|in:add,remove'
        ]);

        $selectedDriverIds = $request->input('selected_drivers');
        $selectedDeclarationIds = $request->input('selected_declarations');
        $selectedPlates = $request->input('selected_plates');
        $action = $request->input('action');

        // Get detailed preview data
        $previewData = $this->generatePreviewData($selectedDeclarationIds, $selectedPlates, $action);

        return view('declarations.bulk-update.step5-preview', compact(
            'previewData', 'selectedDriverIds', 'selectedDeclarationIds', 'selectedPlates', 'action'
        ));
    }

    /**
     * Execute the bulk update - Step 6: Processing
     */
    public function execute(Request $request)
    {
        $request->validate([
            'selected_declarations' => 'required|array|min:1',
            'selected_plates' => 'required|array|min:1',
            'action' => 'required|in:add,remove'
        ]);

        $selectedDeclarationIds = $request->input('selected_declarations');
        $selectedPlates = $request->input('selected_plates');
        $action = $request->input('action');

        return view('declarations.bulk-update.step6-processing', compact(
            'selectedDeclarationIds', 'selectedPlates', 'action'
        ));
    }

    /**
     * API endpoint for processing individual declarations
     */
    public function processDeclaration(Request $request)
    {
        $request->validate([
            'declaration_id' => 'required|string',
            'selected_plates' => 'required|array',
            'action' => 'required|in:add,remove'
        ]);

        $declarationId = $request->input('declaration_id');
        $selectedPlates = $request->input('selected_plates');
        $action = $request->input('action');

        try {
            // Get current declaration data
            $declaration = $this->declarationService->getDeclaration($declarationId);
            $currentPlates = $declaration['declarationVehiclePlateNumber'] ?? [];

            // Calculate new plates based on action
            if ($action === 'add') {
                $newPlates = array_unique(array_merge($currentPlates, $selectedPlates));
            } else { // remove
                $newPlates = array_diff($currentPlates, $selectedPlates);
            }

            // Ensure at least one plate remains
            if (empty($newPlates)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot remove all plates from declaration'
                ], 400);
            }

            // Prepare update data (keeping other required fields from original declaration)
            $updateData = [
                'declarationEndDate' => $declaration['declarationEndDate'],
                'declarationOperationType' => $declaration['declarationOperationType'] ?? ['INTERNATIONAL_CARRIAGE'],
                'declarationTransportType' => $declaration['declarationTransportType'] ?? ['CARRIAGE_OF_GOODS'],
                'declarationVehiclePlateNumber' => array_values($newPlates), // Re-index array
                'otherContactAsTransportManager' => $declaration['otherContactAsTransportManager'] ?? false,
                'otherContactFirstName' => $declaration['otherContactFirstName'] ?? '',
                'otherContactLastName' => $declaration['otherContactLastName'] ?? '',
                'otherContactEmail' => $declaration['otherContactEmail'] ?? '',
                'otherContactPhone' => $declaration['otherContactPhone'] ?? ''
            ];

            // Update the declaration
            $updatedDeclaration = $this->declarationService->updateSubmittedDeclaration($declarationId, $updateData);

            return response()->json([
                'success' => true,
                'declaration_id' => $declarationId,
                'old_plates' => $currentPlates,
                'new_plates' => $newPlates,
                'action' => $action
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process declaration in bulk update', [
                'declaration_id' => $declarationId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'declaration_id' => $declarationId
            ], 500);
        }
    }

    /**
     * Get drivers with their submitted declarations count
     */
    private function getDriversWithDeclarationStats(array $drivers): array
    {
        $driversWithStats = [];

        foreach ($drivers as $driver) {
            $submittedCount = $this->getDriverSubmittedDeclarationsCount($driver['driverId'] ?? '');

            $driversWithStats[] = [
                'driver' => $driver,
                'submitted_count' => $submittedCount
            ];
        }

        return $driversWithStats;
    }

    /**
     * Get count of submitted declarations for a driver
     */
    private function getDriverSubmittedDeclarationsCount(string $driverId): int
    {
        try {
            // Get all submitted declarations for this driver with pagination
            $count = 0;
            $startKey = null;
            $allDeclarations = [];

            do {
                $submittedDeclarations = $this->declarationService->getDeclarationsPaginated(
                    250,
                    $startKey,
                    ['driverId' => $driverId, 'status' => 'SUBMITTED']
                );

                $declarations = $submittedDeclarations['items'] ?? $submittedDeclarations ?? [];

                if (is_array($declarations)) {
                    $count += count($declarations);
                    $allDeclarations = array_merge($allDeclarations, $declarations);
                }

                $startKey = $submittedDeclarations['lastEvaluatedKey'] ?? null;
            } while ($startKey);

            Log::info('Debug: Driver submitted declarations count', [
                'driver_id' => $driverId,
                'count' => $count,
                'sample_declarations' => array_slice($allDeclarations, 0, 2)
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Error in getDriverSubmittedDeclarationsCount', [
                'driver_id' => $driverId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get all submitted declarations for selected drivers
     */
    private function getSubmittedDeclarationsForDrivers(array $driverIds): array
    {
        try {
            $allSubmittedDeclarations = [];

            foreach ($driverIds as $driverId) {
                try {
                    // Get all submitted declarations for this driver with pagination
                    $startKey = null;

                    do {
                        $submittedDeclarations = $this->declarationService->getDeclarationsPaginated(
                            250,
                            $startKey,
                            ['driverId' => $driverId, 'status' => 'SUBMITTED']
                        );

                        $declarations = $submittedDeclarations['items'] ?? $submittedDeclarations ?? [];

                        if (is_array($declarations)) {
                            // Get full declaration details for each one
                            foreach ($declarations as $declaration) {
                                try {
                                    $fullDeclaration = $this->declarationService->getDeclaration($declaration['declarationId']);
                                    $allSubmittedDeclarations[] = $fullDeclaration;
                                } catch (\Exception $e) {
                                    Log::warning('Could not get full declaration details', [
                                        'declaration_id' => $declaration['declarationId'] ?? 'unknown',
                                        'driver_id' => $driverId
                                    ]);
                                }
                            }
                        }

                        $startKey = $submittedDeclarations['lastEvaluatedKey'] ?? null;
                    } while ($startKey);

                } catch (\Exception $e) {
                    Log::warning('Could not get declarations for driver', [
                        'driver_id' => $driverId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $allSubmittedDeclarations;
        } catch (\Exception $e) {
            Log::error('Failed to get submitted declarations for drivers', [
                'driver_ids' => $driverIds,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get current plates data for selected declarations
     */
    private function getCurrentPlatesInDeclarations(array $declarationIds): array
    {
        $platesData = [];

        foreach ($declarationIds as $declarationId) {
            try {
                $declaration = $this->declarationService->getDeclaration($declarationId);
                $platesData[$declarationId] = $declaration['declarationVehiclePlateNumber'] ?? [];
            } catch (\Exception $e) {
                $platesData[$declarationId] = [];
            }
        }

        return $platesData;
    }

    /**
     * Generate preview data showing before/after states
     */
    private function generatePreviewData(array $declarationIds, array $selectedPlates, string $action): array
    {
        $previewData = [];

        foreach ($declarationIds as $declarationId) {
            try {
                $declaration = $this->declarationService->getDeclaration($declarationId);
                $currentPlates = $declaration['declarationVehiclePlateNumber'] ?? [];

                // Calculate new plates
                if ($action === 'add') {
                    $newPlates = array_unique(array_merge($currentPlates, $selectedPlates));
                } else {
                    $newPlates = array_diff($currentPlates, $selectedPlates);
                }

                $previewData[] = [
                    'declaration' => $declaration,
                    'current_plates' => $currentPlates,
                    'new_plates' => array_values($newPlates),
                    'will_change' => $currentPlates !== array_values($newPlates)
                ];

            } catch (\Exception $e) {
                $previewData[] = [
                    'declaration' => ['declarationId' => $declarationId],
                    'current_plates' => [],
                    'new_plates' => [],
                    'will_change' => false,
                    'error' => 'Could not load declaration'
                ];
            }
        }

        return $previewData;
    }
}