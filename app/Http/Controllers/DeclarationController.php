<?php

namespace App\Http\Controllers;

use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\TruckService;
use Illuminate\Http\Request;

class DeclarationController extends Controller
{
    protected DeclarationService $declarationService;
    protected DriverService $driverService;
    protected TruckService $truckService;

    public function __construct(DeclarationService $declarationService, DriverService $driverService, TruckService $truckService)
    {
        $this->declarationService = $declarationService;
        $this->driverService = $driverService;
        $this->truckService = $truckService;
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
            // Get drivers for the dropdown
            $drivers = $this->driverService->getDriversPaginated(250);

            // Get available trucks for plate numbers
            $trucks = $this->truckService->getAvailableTrucks();

            return view('declarations.create', [
                'drivers' => $drivers['items'] ?? [],
                'trucks' => $trucks,
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
            'declarationVehiclePlateNumber' => 'required|array|min:1',
            'declarationVehiclePlateNumber.*' => 'required|string|max:20',
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


        // Ensure boolean conversion for the API
        $validated['otherContactAsTransportManager'] = (bool) $validated['otherContactAsTransportManager'];

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

            // Get drivers for the dropdown
            $drivers = $this->driverService->getDriversPaginated(250);

            // Get available trucks for plate numbers
            $trucks = $this->truckService->getAvailableTrucks();

            return view('declarations.edit', [
                'declaration' => $declaration,
                'drivers' => $drivers['items'] ?? [],
                'trucks' => $trucks,
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
            'declarationVehiclePlateNumber' => 'required|array|min:1',
            'declarationVehiclePlateNumber.*' => 'string|max:20',
            'driverId' => 'required|string',
            'otherContactAsTransportManager' => 'boolean',
            'otherContactFirstName' => 'nullable|string|max:255',
            'otherContactLastName' => 'nullable|string|max:255',
            'otherContactEmail' => 'nullable|email|max:255',
            'otherContactPhone' => 'nullable|string|max:20',
        ]);

        // Clean up array values
        $validated['declarationVehiclePlateNumber'] = array_filter($validated['declarationVehiclePlateNumber']);

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
}