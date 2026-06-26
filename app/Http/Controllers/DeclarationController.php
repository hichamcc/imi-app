<?php

namespace App\Http\Controllers;

use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\PlateNumberService;
use App\Services\TruckService;
use Illuminate\Http\Request;

class DeclarationController extends Controller
{
    protected DeclarationService $declarationService;
    protected DriverService $driverService;
    protected TruckService $truckService;
    protected PlateNumberService $plateNumberService;

    public function __construct(
        DeclarationService $declarationService,
        DriverService $driverService,
        TruckService $truckService,
        PlateNumberService $plateNumberService,
    ) {
        $this->declarationService = $declarationService;
        $this->driverService = $driverService;
        $this->truckService = $truckService;
        $this->plateNumberService = $plateNumberService;
    }

    /**
     * Fetch the API plate-number register and bucket by transport+weight.
     * Returns ['goods_light' => [...], 'goods_heavy' => [...], 'passengers' => [...]].
     * Falls back to the local trucks table on API error so the form is still usable.
     */
    private function fetchPlateGroups(): array
    {
        $groups = ['goods_light' => [], 'goods_heavy' => [], 'passengers' => [], 'source' => 'api', 'error' => null];

        try {
            $items = $this->plateNumberService->all();
            foreach ($items as $p) {
                $entry = [
                    'plate' => $p['plateNumber'] ?? null,
                    'country' => $p['registrationCountry'] ?? null,
                    'id' => $p['plateNumberId'] ?? null,
                ];
                if (!$entry['plate']) continue;

                $type = $p['transportType'] ?? null;
                $weight = $p['vehicleWeight'] ?? '';

                if ($type === 'CARRIAGE_OF_PASSENGERS') {
                    $groups['passengers'][] = $entry;
                } elseif ($type === 'CARRIAGE_OF_GOODS') {
                    if ($weight === 'LIGHT') $groups['goods_light'][] = $entry;
                    else $groups['goods_heavy'][] = $entry; // default heavy (covers HEAVY + empty)
                }
            }
        } catch (\Throwable $e) {
            $groups['source'] = 'local';
            $groups['error'] = $e->getMessage();
            \Log::warning('Failed to load API plate-numbers, falling back to local trucks', ['error' => $e->getMessage()]);

            foreach (auth()->user()->trucks()->orderBy('plate')->get() as $t) {
                $entry = ['plate' => $t->plate, 'country' => $t->registration_country, 'id' => null];
                if ($t->carriage_type === \App\Models\Truck::CARRIAGE_PASSENGERS) {
                    $groups['passengers'][] = $entry;
                } elseif ($t->weight_type === \App\Models\Truck::WEIGHT_LIGHT) {
                    $groups['goods_light'][] = $entry;
                } else {
                    $groups['goods_heavy'][] = $entry;
                }
            }
        }

        return $groups;
    }

    /**
     * Reshape validated form data into the API declaration payload — split light/heavy
     * for goods, and only set declarationVehiclePlateNumber for passengers.
     */
    private function buildPlatePayload(array $validated): array
    {
        $payload = [];
        $transportTypes = $validated['declarationTransportType'] ?? [];

        if (in_array('CARRIAGE_OF_GOODS', $transportTypes, true)) {
            $light = array_values(array_filter($validated['declarationVehiclePlateNumberLight'] ?? []));
            $heavy = array_values(array_filter($validated['declarationVehiclePlateNumberHeavy'] ?? []));
            if (!empty($light)) $payload['declarationVehiclePlateNumberLight'] = $light;
            if (!empty($heavy)) $payload['declarationVehiclePlateNumberHeavy'] = $heavy;
        }

        if (in_array('CARRIAGE_OF_PASSENGERS', $transportTypes, true)) {
            $passenger = array_values(array_filter($validated['declarationVehiclePlateNumber'] ?? []));
            if (!empty($passenger)) $payload['declarationVehiclePlateNumber'] = $passenger;
        }

        return $payload;
    }

    /**
     * Display a listing of declarations
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 50);
        $startKey = $request->get('startKey');
        $status = $request->get('status');
        $postingCountry = $request->get('postingCountry');
        $driverId = $request->get('driverId');
        $endDateFrom = $request->get('endDateFrom');
        $endDateTo = $request->get('endDateTo');

        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        if ($postingCountry) {
            $filters['postingCountry'] = $postingCountry;
        }
        if ($driverId) {
            $filters['driverId'] = $driverId;
        }
        if ($endDateFrom) {
            $filters['endDateFrom'] = $endDateFrom;
        }
        if ($endDateTo) {
            $filters['endDateTo'] = $endDateTo;
        }

        try {
            $declarations = $this->declarationService->getDeclarationsPaginated($limit, $startKey, $filters);

            return view('declarations.index', compact(
                'declarations',
                'status',
                'postingCountry',
                'driverId',
                'endDateFrom',
                'endDateTo',
                'limit',
                'startKey'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load declarations: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new declaration
     */
    public function create()
    {
        try {
            $drivers = $this->driverService->getDriversPaginated(250);
            $plateGroups = $this->fetchPlateGroups();

            return view('declarations.create', [
                'drivers' => $drivers['items'] ?? [],
                'plateGroups' => $plateGroups,
                'countries' => DeclarationService::getPostingCountries(),
                'operationTypes' => DeclarationService::getOperationTypes(),
                'transportTypes' => DeclarationService::getTransportTypes()
            ]);
        } catch (\Exception $e) {
            return redirect()->route('declarations.index')->with('error', 'Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created declaration
     */
    public function store(Request $request)
    {


        $validated = $request->validate([
            'declarationPostingCountries' => 'required|array|min:1',
            'declarationPostingCountries.*' => 'string|size:2',
            'declarationVehiclePlateNumber' => 'nullable|array',
            'declarationVehiclePlateNumber.*' => 'string|max:20',
            'declarationVehiclePlateNumberLight' => 'nullable|array',
            'declarationVehiclePlateNumberLight.*' => 'string|max:20',
            'declarationVehiclePlateNumberHeavy' => 'nullable|array',
            'declarationVehiclePlateNumberHeavy.*' => 'string|max:20',
            'declarationStartDate' => 'required|date|date_format:Y-m-d',
            'declarationEndDate' => 'required|date|date_format:Y-m-d|after:declarationStartDate',
            'declarationOperationType' => 'required|array|min:1|max:2',
            'declarationOperationType.*' => 'string|in:CABOTAGE_OPERATIONS,INTERNATIONAL_CARRIAGE',
            'declarationTransportType' => 'required|array|min:1|max:2',
            'declarationTransportType.*' => 'string|in:CARRIAGE_OF_GOODS,CARRIAGE_OF_PASSENGERS',
            'driverId' => 'required|string',
            'otherContactAsTransportManager' => 'required|boolean',
            'otherContactFirstName' => 'nullable|string|max:255',
            'otherContactLastName' => 'nullable|string|max:255',
            'otherContactEmail' => 'nullable|email|max:255',
            'otherContactPhone' => 'nullable|string|max:20',
        ]);

        $validated['otherContactAsTransportManager'] = (bool) $validated['otherContactAsTransportManager'];

        $platePayload = $this->buildPlatePayload($validated);
        if (empty($platePayload)) {
            return redirect()->back()->withInput()
                ->with('error', 'Select at least one plate number for the chosen transport type(s).');
        }
        // Strip out the form-level plate arrays — only the API-shaped fields go on the wire
        unset(
            $validated['declarationVehiclePlateNumber'],
            $validated['declarationVehiclePlateNumberLight'],
            $validated['declarationVehiclePlateNumberHeavy'],
        );
        $validated = array_merge($validated, $platePayload);

        $selectedCountries = $validated['declarationPostingCountries'];
        $createdDeclarations = [];
        $errors = [];

        try {
            // Create a separate declaration for each selected country
            foreach ($selectedCountries as $country) {
                try {
                    $declarationData = $validated;
                    $declarationData['declarationPostingCountry'] = $country;
                    unset($declarationData['declarationPostingCountries']); // Remove the array field

                    $declaration = $this->declarationService->createDeclaration($declarationData);
                    $createdDeclarations[] = [
                        'id' => $declaration['declarationId'],
                        'country' => $country
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Failed to create declaration for {$country}: " . $e->getMessage();
                }
            }

            // Determine success message and redirect
            if (count($createdDeclarations) > 0) {
                $successCount = count($createdDeclarations);
                $errorCount = count($errors);

                if ($errorCount === 0) {
                    $message = "Successfully created {$successCount} declaration(s) for the selected countries.";
                    $messageType = 'success';
                } else {
                    $message = "Created {$successCount} declaration(s) successfully, but {$errorCount} failed. " . implode(' ', $errors);
                    $messageType = 'warning';
                }

                // Redirect to the first created declaration if only one, otherwise to declarations list
                if (count($createdDeclarations) === 1) {
                    return redirect()->route('declarations.show', $createdDeclarations[0]['id'])
                        ->with($messageType, $message);
                } else {
                    return redirect()->route('declarations.index')
                        ->with($messageType, $message);
                }
            } else {
                // All declarations failed to create
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to create any declarations: ' . implode(' ', $errors));
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create declarations: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified declaration
     */
    public function show(string $id)
    {
        try {
            $declaration = $this->declarationService->getDeclaration($id);
            return view('declarations.show', compact('declaration'));
        } catch (\Exception $e) {
            return redirect()->route('declarations.index')
                ->with('error', 'Failed to load declaration: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified declaration
     */
    public function edit(string $id)
    {
        try {
            $declaration = $this->declarationService->getDeclaration($id);

            // Check if declaration can be edited (only DRAFT status)
            if (($declaration['declarationStatus'] ?? '') !== 'DRAFT') {
                return redirect()->route('declarations.show', $id)
                    ->with('error', 'Only draft declarations can be edited.');
            }

            $drivers = $this->driverService->getDriversPaginated(250);
            $plateGroups = $this->fetchPlateGroups();

            return view('declarations.edit', [
                'declaration' => $declaration,
                'drivers' => $drivers['items'] ?? [],
                'plateGroups' => $plateGroups,
                'countries' => DeclarationService::getPostingCountries(),
                'operationTypes' => DeclarationService::getOperationTypes(),
                'transportTypes' => DeclarationService::getTransportTypes()
            ]);
        } catch (\Exception $e) {
            return redirect()->route('declarations.index')
                ->with('error', 'Failed to load declaration: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified declaration
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'declarationPostingCountry' => 'required|string|size:2',
            'declarationStartDate' => 'required|date|date_format:Y-m-d',
            'declarationEndDate' => 'required|date|date_format:Y-m-d|after:declarationStartDate',
            'declarationOperationType' => 'required|array|min:1|max:2',
            'declarationOperationType.*' => 'string|in:CABOTAGE_OPERATIONS,INTERNATIONAL_CARRIAGE',
            'declarationTransportType' => 'required|array|min:1|max:2',
            'declarationTransportType.*' => 'string|in:CARRIAGE_OF_GOODS,CARRIAGE_OF_PASSENGERS',
            'declarationVehiclePlateNumber' => 'nullable|array',
            'declarationVehiclePlateNumber.*' => 'string|max:20',
            'declarationVehiclePlateNumberLight' => 'nullable|array',
            'declarationVehiclePlateNumberLight.*' => 'string|max:20',
            'declarationVehiclePlateNumberHeavy' => 'nullable|array',
            'declarationVehiclePlateNumberHeavy.*' => 'string|max:20',
            'driverId' => 'required|string',
            'otherContactAsTransportManager' => 'boolean',
            'otherContactFirstName' => 'nullable|string|max:255',
            'otherContactLastName' => 'nullable|string|max:255',
            'otherContactEmail' => 'nullable|email|max:255',
            'otherContactPhone' => 'nullable|string|max:20',
        ]);

        $platePayload = $this->buildPlatePayload($validated);
        if (empty($platePayload)) {
            return redirect()->back()->withInput()
                ->with('error', 'Select at least one plate number for the chosen transport type(s).');
        }
        unset(
            $validated['declarationVehiclePlateNumber'],
            $validated['declarationVehiclePlateNumberLight'],
            $validated['declarationVehiclePlateNumberHeavy'],
        );
        $validated = array_merge($validated, $platePayload);

        try {
            $declaration = $this->declarationService->updateDeclaration($id, $validated);
            return redirect()->route('declarations.show', $id)
                ->with('success', 'Declaration updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update declaration: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified declaration
     */
    public function destroy(string $id)
    {
        try {
            // First get the declaration to check its status
            $declaration = $this->declarationService->getDeclaration($id);

            if (($declaration['declarationStatus'] ?? '') !== 'DRAFT') {
                return redirect()->back()
                    ->with('error', 'Only draft declarations can be deleted.');
            }

            $this->declarationService->deleteDeclaration($id);
            return redirect()->route('declarations.index')
                ->with('success', 'Declaration deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete declaration: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a submitted declaration
     */
    public function editSubmitted(string $id)
    {
        try {
            $declaration = $this->declarationService->getDeclaration($id);

            // Check if declaration is actually submitted
            if (($declaration['declarationStatus'] ?? '') !== 'SUBMITTED') {
                return redirect()->route('declarations.edit', $id)
                    ->with('info', 'This declaration is not submitted. Use regular edit form.');
            }

            $plateGroups = $this->fetchPlateGroups();

            // Aggregate all known plates from the API to spot any missing-from-register plates
            $allKnownPlates = array_merge(
                array_column($plateGroups['goods_light'], 'plate'),
                array_column($plateGroups['goods_heavy'], 'plate'),
                array_column($plateGroups['passengers'], 'plate'),
            );

            $declarationPlates = array_merge(
                $declaration['declarationVehiclePlateNumber'] ?? [],
                $declaration['declarationVehiclePlateNumberLight'] ?? [],
                $declaration['declarationVehiclePlateNumberHeavy'] ?? [],
            );
            $missingPlates = array_values(array_diff($declarationPlates, $allKnownPlates));

            return view('declarations.edit-submitted', compact('declaration', 'plateGroups', 'missingPlates'));
        } catch (\Exception $e) {
            return redirect()->route('declarations.index')
                ->with('error', 'Failed to load declaration: ' . $e->getMessage());
        }
    }

    /**
     * Update a submitted declaration (limited fields only)
     */
    public function updateSubmitted(Request $request, string $id)
    {
        // Validate only fields that can be updated for submitted declarations
        $validated = $request->validate([
            'declarationEndDate' => 'required|date|date_format:Y-m-d',
            'declarationOperationType' => 'required|array|min:1|max:2',
            'declarationOperationType.*' => 'string|in:CABOTAGE_OPERATIONS,INTERNATIONAL_CARRIAGE',
            'declarationTransportType' => 'required|array|min:1|max:2',
            'declarationTransportType.*' => 'string|in:CARRIAGE_OF_GOODS,CARRIAGE_OF_PASSENGERS',
            'declarationVehiclePlateNumber' => 'nullable|array',
            'declarationVehiclePlateNumber.*' => 'string|max:20',
            'declarationVehiclePlateNumberLight' => 'nullable|array',
            'declarationVehiclePlateNumberLight.*' => 'string|max:20',
            'declarationVehiclePlateNumberHeavy' => 'nullable|array',
            'declarationVehiclePlateNumberHeavy.*' => 'string|max:20',
            'otherContactAsTransportManager' => 'boolean',
            'otherContactFirstName' => 'nullable|string|max:255',
            'otherContactLastName' => 'nullable|string|max:255',
            'otherContactEmail' => 'nullable|email|max:255',
            'otherContactPhone' => 'nullable|string|max:20',
            'otherContactAddressStreet' => 'nullable|string|max:255',
            'otherContactAddressCity' => 'nullable|string|max:100',
            'otherContactAddressCountry' => 'nullable|string|size:2',
            'otherContactAddressPostCode' => 'nullable|string|max:20',
            'driverEmail' => 'nullable|email|max:255',
        ]);

        $platePayload = $this->buildPlatePayload($validated);
        if (empty($platePayload)) {
            return redirect()->back()->withInput()
                ->with('error', 'Select at least one plate number for the chosen transport type(s).');
        }
        unset(
            $validated['declarationVehiclePlateNumber'],
            $validated['declarationVehiclePlateNumberLight'],
            $validated['declarationVehiclePlateNumberHeavy'],
        );
        $validated = array_merge($validated, $platePayload);

        // Convert checkbox to boolean for API
        $validated['otherContactAsTransportManager'] = isset($validated['otherContactAsTransportManager']) && $validated['otherContactAsTransportManager'];

        try {
            $declaration = $this->declarationService->updateSubmittedDeclaration($id, $validated);
            return redirect()->route('declarations.show', $id)
                ->with('success', 'Submitted declaration updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update submitted declaration: ' . $e->getMessage());
        }
    }

    /**
     * Submit a declaration
     */
    public function submit(string $id)
    {
        try {
            // First get the declaration to check its status
            $declaration = $this->declarationService->getDeclaration($id);

            if (($declaration['declarationStatus'] ?? '') !== 'DRAFT') {
                return redirect()->back()
                    ->with('error', 'Only draft declarations can be submitted.');
            }

            $this->declarationService->submitDeclaration($id);

            // Send email to driver if they have an email
            $this->sendDeclarationEmailIfDriverHasEmail($id, $declaration);

            return redirect()->route('declarations.show', $id)
                ->with('success', 'Declaration submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to submit declaration: ' . $e->getMessage());
        }
    }

    /**
     * Withdraw a declaration
     */
    public function withdraw(string $id)
    {
        try {
            // First get the declaration to check its status
            $declaration = $this->declarationService->getDeclaration($id);

            if (($declaration['declarationStatus'] ?? '') !== 'SUBMITTED') {
                return redirect()->back()
                    ->with('error', 'Only submitted declarations can be withdrawn.');
            }

            $this->declarationService->withdrawDeclaration($id);
            return redirect()->route('declarations.show', $id)
                ->with('success', 'Declaration withdrawn successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to withdraw declaration: ' . $e->getMessage());
        }
    }

    /**
     * Get truck plates for a specific driver (AJAX endpoint)
     */
    public function getDriverTruckPlates(string $driverId)
    {
        try {
            $plates = $this->truckService->getTruckPlatesForDriver($driverId);
            return response()->json(['plates' => $plates]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get truck plates'], 500);
        }
    }

    /**
     * Print declaration (generate PDF)
     */
    public function print(Request $request, string $id)
    {
        $validated = $request->validate([
            'declarationLanguage' => 'required|string|in:bg,cs,da,de,et,el,en,es,fr,fi,ga,hr,hu,it,lv,lt,mt,nl,no,pl,pt,ro,sk,sl,sv'
        ]);

        try {
            $result = $this->declarationService->printDeclaration($id, $validated['declarationLanguage']);

            // The API returns the direct S3 URL as a string
            $pdfUrl = is_array($result) ? ($result['url'] ?? $result['data'] ?? '') : $result;

            return response()->json([
                'success' => true,
                'url' => $pdfUrl,
                'message' => 'PDF generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Email declaration
     */
    public function email(Request $request, string $id)
    {
        $validated = $request->validate([
            'emailAddress' => 'required|email',
            'declarationLanguage' => 'required|string|in:bg,cs,da,de,et,el,en,es,fr,fi,ga,hr,hu,it,lv,lt,mt,nl,no,pl,pt,ro,sk,sl,sv'
        ]);

        try {
            $this->declarationService->emailDeclaration($id, $validated['emailAddress'], $validated['declarationLanguage']);

            return response()->json([
                'success' => true,
                'message' => 'Declaration sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send declaration email to driver if they have an email in DriverProfile
     */
    private function sendDeclarationEmailIfDriverHasEmail(string $declarationId, array $declaration): void
    {
        try {
            $driverId = $declaration['driverId'] ?? null;
            if (!$driverId) {
                return;
            }

            $driverEmail = \App\Models\DriverProfile::getDriverEmail($driverId);
            if (!$driverEmail) {
                return;
            }

            $this->declarationService->emailDeclaration($declarationId, $driverEmail, 'en');

            \Log::info('Declaration email auto-sent after submit', [
                'declaration_id' => $declarationId,
                'driver_id' => $driverId,
                'driver_email' => $driverEmail,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to auto-send declaration email after submit', [
                'declaration_id' => $declarationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bulk delete draft declarations
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'declaration_ids' => 'required|array|min:1',
            'declaration_ids.*' => 'string'
        ]);

        try {
            $declarationIds = $request->declaration_ids;
            $deletedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($declarationIds as $declarationId) {
                try {
                    // Get declaration to check status
                    $declaration = $this->declarationService->getDeclaration($declarationId);

                    // Only delete DRAFT declarations
                    if (($declaration['declarationStatus'] ?? '') !== 'DRAFT') {
                        $skippedCount++;
                        $errors[] = "Declaration {$declarationId} is not a draft (status: {$declaration['declarationStatus']})";
                        continue;
                    }

                    // Delete the declaration
                    $this->declarationService->deleteDeclaration($declarationId);
                    $deletedCount++;

                    \Log::info('Declaration deleted via bulk action', [
                        'declaration_id' => $declarationId,
                        'deleted_by' => auth()->id(),
                        'deleted_at' => now()->toDateTimeString()
                    ]);

                } catch (\Exception $e) {
                    $errors[] = "Failed to delete {$declarationId}: " . $e->getMessage();
                    \Log::error('Bulk delete error', [
                        'declaration_id' => $declarationId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $success = $deletedCount > 0;
            $message = "Deleted {$deletedCount} declaration(s)";

            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount} (not draft)";
            }

            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'error_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Bulk delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete declarations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk withdraw submitted declarations
     */
    public function bulkWithdraw(Request $request)
    {
        $request->validate([
            'declaration_ids' => 'required|array|min:1',
            'declaration_ids.*' => 'string'
        ]);

        try {
            $declarationIds = $request->declaration_ids;
            $withdrawnCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($declarationIds as $declarationId) {
                try {
                    // Get declaration to check status
                    $declaration = $this->declarationService->getDeclaration($declarationId);

                    // Only withdraw SUBMITTED declarations
                    if (($declaration['declarationStatus'] ?? '') !== 'SUBMITTED') {
                        $skippedCount++;
                        $errors[] = "Declaration {$declarationId} is not submitted (status: {$declaration['declarationStatus']})";
                        continue;
                    }

                    // Withdraw the declaration
                    $this->declarationService->withdrawDeclaration($declarationId);
                    $withdrawnCount++;

                    \Log::info('Declaration withdrawn via bulk action', [
                        'declaration_id' => $declarationId,
                        'withdrawn_by' => auth()->id(),
                        'withdrawn_at' => now()->toDateTimeString()
                    ]);

                } catch (\Exception $e) {
                    $errors[] = "Failed to withdraw {$declarationId}: " . $e->getMessage();
                    \Log::error('Bulk withdraw error', [
                        'declaration_id' => $declarationId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $success = $withdrawnCount > 0;
            $message = "Withdrawn {$withdrawnCount} declaration(s)";

            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount} (not submitted)";
            }

            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'withdrawn_count' => $withdrawnCount,
                'skipped_count' => $skippedCount,
                'error_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Bulk withdraw failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to withdraw declarations: ' . $e->getMessage()
            ], 500);
        }
    }
}