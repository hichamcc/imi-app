<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Services\TruckService;
use App\Services\DriverService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TruckController extends Controller
{
    protected TruckService $truckService;
    protected DriverService $driverService;

    public function __construct(TruckService $truckService, DriverService $driverService)
    {
        $this->truckService = $truckService;
        $this->driverService = $driverService;
    }

    /**
     * Display a listing of trucks
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $trucks = $this->truckService->getTrucksPaginated(15, $filters);
        $statuses = Truck::getStatuses();

        // Get drivers for assignment dropdown
        try {
            $drivers = $this->driverService->getDriversPaginated(250);
            $availableDrivers = $drivers['items'] ?? [];
        } catch (\Exception $e) {
            $availableDrivers = [];
        }

        return view('trucks.index', compact('trucks', 'statuses', 'filters', 'availableDrivers'));
    }

    /**
     * Show the form for creating a new truck
     */
    public function create()
    {
        $statuses = Truck::getStatuses();
        $countries = \App\Services\DeclarationService::getPostingCountries();
        $registrationCountries = Truck::getRegistrationCountries();
        $carriageTypes = Truck::getCarriageTypes();
        $weightTypes = Truck::getWeightTypes();
        return view('trucks.create', compact('statuses', 'countries', 'registrationCountries', 'carriageTypes', 'weightTypes'));
    }

    /**
     * Store a newly created truck
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('trucks')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
            'capacity_tons' => 'required|numeric|min:0.01|max:999999.99',
            'status' => 'required|in:Available,In-Transit,Maintenance,Retired',
            'countries' => 'nullable|array',
            'countries.*' => 'in:' . implode(',', array_keys(\App\Services\DeclarationService::getPostingCountries())),
            'registration_country' => ['nullable', Rule::in(array_keys(Truck::getRegistrationCountries()))],
            'carriage_type' => ['nullable', Rule::in(array_keys(Truck::getCarriageTypes()))],
            'weight_type' => ['nullable', Rule::in(array_keys(Truck::getWeightTypes()))],
        ]);

        $validated['weight_type'] = $this->normalizeWeightType($validated['carriage_type'] ?? null, $validated['weight_type'] ?? Truck::WEIGHT_HEAVY);

        try {
            $truck = $this->truckService->createTruck($validated);
            return redirect()->route('trucks.show', $truck->id)
                ->with('success', 'Truck created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create truck: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified truck
     */
    public function show(string $id)
    {
        try {
            $truck = $this->truckService->getTruck($id);

            // Get drivers for assignment dropdown
            $drivers = $this->driverService->getDriversPaginated(250);
            $availableDrivers = $drivers['items'] ?? [];

            return view('trucks.show', compact('truck', 'availableDrivers'));
        } catch (\Exception $e) {
            return redirect()->route('trucks.index')
                ->with('error', 'Failed to load truck: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified truck
     */
    public function edit(string $id)
    {
        try {
            $truck = $this->truckService->getTruck($id);
            $statuses = Truck::getStatuses();
            $countries = \App\Services\DeclarationService::getPostingCountries();
            $registrationCountries = Truck::getRegistrationCountries();
            $carriageTypes = Truck::getCarriageTypes();
            $weightTypes = Truck::getWeightTypes();
            return view('trucks.edit', compact('truck', 'statuses', 'countries', 'registrationCountries', 'carriageTypes', 'weightTypes'));
        } catch (\Exception $e) {
            return redirect()->route('trucks.index')
                ->with('error', 'Failed to load truck: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified truck
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('trucks')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($id)
            ],
            'capacity_tons' => 'required|numeric|min:0.01|max:999999.99',
            'status' => 'required|in:Available,In-Transit,Maintenance,Retired',
            'countries' => 'nullable|array',
            'countries.*' => 'in:' . implode(',', array_keys(\App\Services\DeclarationService::getPostingCountries())),
            'registration_country' => ['nullable', Rule::in(array_keys(Truck::getRegistrationCountries()))],
            'carriage_type' => ['nullable', Rule::in(array_keys(Truck::getCarriageTypes()))],
            'weight_type' => ['nullable', Rule::in(array_keys(Truck::getWeightTypes()))],
        ]);

        $validated['weight_type'] = $this->normalizeWeightType($validated['carriage_type'] ?? null, $validated['weight_type'] ?? Truck::WEIGHT_HEAVY);

        try {
            $truck = $this->truckService->updateTruck($id, $validated);
            return redirect()->route('trucks.show', $truck->id)
                ->with('success', 'Truck updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update truck: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified truck
     */
    public function destroy(string $id)
    {
        try {
            $this->truckService->deleteTruck($id);
            return redirect()->route('trucks.index')
                ->with('success', 'Truck deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete truck: ' . $e->getMessage());
        }
    }

    /**
     * Assign a driver to a truck
     */
    public function assignDriver(Request $request, string $id)
    {
        $validated = $request->validate([
            'driver_id' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->truckService->assignDriver($id, $validated['driver_id'], $validated['notes'] ?? null);
            return redirect()->route('trucks.show', $id)
                ->with('success', 'Driver assigned successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to assign driver: ' . $e->getMessage());
        }
    }

    /**
     * Unassign a driver from a truck
     */
    public function unassignDriver(string $assignmentId)
    {
        try {
            $assignment = $this->truckService->unassignDriver($assignmentId);
            return redirect()->route('trucks.show', $assignment->truck_id)
                ->with('success', 'Driver unassigned successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unassign driver: ' . $e->getMessage());
        }
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('trucks.import');
    }

    /**
     * Import trucks from text file
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'truck_file' => 'required|file|mimes:txt,csv|max:2048'
        ]);

        try {
            $file = $request->file('truck_file');
            $content = file_get_contents($file->getPathname());
            $lines = array_filter(array_map('trim', explode("\n", $content)));

            $imported = 0;
            $errors = [];
            $allCountries = array_keys(\App\Services\DeclarationService::getPostingCountries());

            foreach ($lines as $lineNumber => $line) {
                if (empty($line)) continue;

                // Split by semicolon or tab, expecting: plate_number;truck_name or plate_number	truck_name
                $parts = preg_split('/[;\t]/', $line, -1, PREG_SPLIT_NO_EMPTY);

                if (count($parts) < 2) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Invalid format. Expected 'plate_number;truck_name' or 'plate_number	truck_name'";
                    continue;
                }

                $plateNumber = trim($parts[0]);
                $truckName = trim($parts[1]);

                // Validate data
                if (empty($plateNumber) || empty($truckName)) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Plate number and truck name cannot be empty";
                    continue;
                }

                // Check if truck already exists for this user
                $existingTruck = Truck::where('user_id', auth()->id())
                    ->where('plate', $plateNumber)
                    ->first();

                if ($existingTruck) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Truck with plate '{$plateNumber}' already exists";
                    continue;
                }

                try {
                    // Create truck with default values
                    Truck::create([
                        'user_id' => auth()->id(),
                        'name' => $truckName,
                        'plate' => $plateNumber,
                        'capacity_tons' => 40.0, // Default capacity
                        'status' => 'Available', // Default status
                        'countries' => $allCountries, // Associate with all countries by default
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Failed to create truck - " . $e->getMessage();
                }
            }

            $message = "Import completed! {$imported} trucks imported successfully.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " errors occurred.";
                \Log::warning('Truck import errors', ['errors' => $errors]);
                return redirect()->route('trucks.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('trucks.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Truck import failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to process import: ' . $e->getMessage());
        }
    }

    /**
     * Force weight_type = N/A when carriage_type is passengers (per provider spec).
     */
    private function normalizeWeightType(?string $carriageType, ?string $weightType): string
    {
        if ($carriageType === Truck::CARRIAGE_PASSENGERS) {
            return Truck::WEIGHT_NA;
        }
        return in_array($weightType, [Truck::WEIGHT_LIGHT, Truck::WEIGHT_HEAVY], true)
            ? $weightType
            : Truck::WEIGHT_HEAVY;
    }

    /**
     * Export the current user's trucks to the provider's Excel template format.
     */
    public function exportTemplate()
    {
        $trucks = Truck::where('user_id', auth()->id())->orderBy('plate')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Sheet 1: ReferenceData (mirrors the provider's template)
        $ref = $spreadsheet->getActiveSheet();
        $ref->setTitle('ReferenceData');
        $ref->fromArray(['Registration country', 'Type of carriage', 'Vehicle weight'], null, 'A1');
        $row = 2;
        $countries = array_keys(Truck::getRegistrationCountries());
        $carriages = [Truck::CARRIAGE_GOODS, Truck::CARRIAGE_PASSENGERS];
        $weights = [Truck::WEIGHT_LIGHT, Truck::WEIGHT_HEAVY, Truck::WEIGHT_NA];
        $maxRows = max(count($countries), count($carriages), count($weights));
        for ($i = 0; $i < $maxRows; $i++) {
            $ref->setCellValue("A" . ($row + $i), $countries[$i] ?? null);
            $ref->setCellValue("B" . ($row + $i), $carriages[$i] ?? null);
            $ref->setCellValue("C" . ($row + $i), $weights[$i] ?? null);
        }

        // Sheet 2: Template — actual fleet data, matching provider's column names exactly
        $tpl = $spreadsheet->createSheet();
        $tpl->setTitle('Template');
        $tpl->fromArray([
            'vehicle.registrationCountry',
            'vehicle.carriageType',
            'vehicle.weightType',
            'vehicle.plateNumber',
        ], null, 'A1');

        $r = 2;
        foreach ($trucks as $truck) {
            $tpl->setCellValue("A{$r}", $truck->registration_country);
            $tpl->setCellValue("B{$r}", $truck->carriage_type);
            $tpl->setCellValue("C{$r}", $truck->weight_type);
            $tpl->setCellValueExplicit("D{$r}", $truck->plate, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }

        $spreadsheet->setActiveSheetIndex(1);

        $filename = 'fleet-upload-' . now()->format('Y-m-d') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Bulk update only the API-related vehicle fields on selected trucks.
     */
    public function bulkUpdateVehicleFields(Request $request)
    {
        $validated = $request->validate([
            'truck_ids' => 'required|array|min:1',
            'truck_ids.*' => 'integer|exists:trucks,id',
            'registration_country' => ['nullable', Rule::in(array_keys(Truck::getRegistrationCountries()))],
            'carriage_type' => ['nullable', Rule::in(array_keys(Truck::getCarriageTypes()))],
            'weight_type' => ['nullable', Rule::in(array_keys(Truck::getWeightTypes()))],
        ]);

        $updates = array_filter([
            'registration_country' => $validated['registration_country'] ?? null,
            'carriage_type' => $validated['carriage_type'] ?? null,
            'weight_type' => $validated['weight_type'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No fields selected to update.',
            ], 422);
        }

        // If carriage_type=passengers is being applied, force weight_type=N/A
        if (($updates['carriage_type'] ?? null) === Truck::CARRIAGE_PASSENGERS) {
            $updates['weight_type'] = Truck::WEIGHT_NA;
        }

        $count = Truck::whereIn('id', $validated['truck_ids'])
            ->where('user_id', auth()->id())
            ->update($updates);

        return response()->json([
            'success' => true,
            'message' => "{$count} truck(s) updated.",
            'updated' => $count,
        ]);
    }
}
