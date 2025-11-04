<?php

namespace App\Http\Controllers;

use App\Services\DriverService;
use App\Services\DeclarationService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected DriverService $driverService;
    protected DeclarationService $declarationService;

    public function __construct(DriverService $driverService, DeclarationService $declarationService)
    {
        $this->driverService = $driverService;
        $this->declarationService = $declarationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 50);
        $startKey = $request->get('startKey');
        $term = $request->get('term');
        $withActiveDeclarations = $request->get('withActiveDeclarations');
        $byLastName = $request->get('byLastName');
        $dateOfBirth = $request->get('dateOfBirth');

        $filters = [];
        if ($term) {
            $filters['term'] = $term;
        }
        if ($withActiveDeclarations !== null) {
            $filters['withActiveDeclarations'] = $withActiveDeclarations;
        }
        if ($byLastName !== null) {
            $filters['byLastName'] = $byLastName;
        }
        if ($dateOfBirth) {
            $filters['dateOfBirth'] = $dateOfBirth;
        }

        try {
            $drivers = $this->driverService->getDriversPaginatedWithProfiles($limit, $startKey, $filters);

            // Add active declaration countries to drivers
            if (isset($drivers['items'])) {
                $drivers['items'] = $this->driverService->getDriversWithActiveCountries($drivers['items']);
            } elseif (is_array($drivers)) {
                $drivers = $this->driverService->getDriversWithActiveCountries($drivers);
            }

            return view('drivers.index', compact('drivers', 'term', 'limit', 'startKey', 'withActiveDeclarations', 'byLastName', 'dateOfBirth'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load drivers: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('drivers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driverLatinFirstName' => 'required|string|max:255',
            'driverLatinLastName' => 'required|string|max:255',
            'driverDateOfBirth' => 'required|date|date_format:Y-m-d',
            'driverLicenseNumber' => 'required|string|max:50',
            'driverDocumentType' => 'required|string|in:IDCARD,PASSPORT,DRIVINGLICENSE',
            'driverDocumentNumber' => 'required|string|max:50',
            'driverDocumentIssuingCountry' => 'required|string|size:2',
            'driverAddressStreet' => 'required|string|max:255',
            'driverAddressPostCode' => 'required|string|max:20',
            'driverAddressCity' => 'required|string|max:100',
            'driverAddressCountry' => 'required|string|size:2',
            'driverContractStartDate' => 'required|date|date_format:Y-m-d',
            'driverApplicableLaw' => 'required|string|size:2',
        ]);

        try {
            $driver = $this->driverService->createDriver($validated);
            return redirect()->route('drivers.show', $driver['driverId'])->with('success', 'Driver created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create driver: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $driver = $this->driverService->getDriverWithProfile($id);

            // Get driver's declarations by matching driver name
            $declarations = $this->getDriverDeclarations($driver);

            return view('drivers.show', compact('driver', 'declarations'));
        } catch (\Exception $e) {
            return redirect()->route('drivers.index')->with('error', 'Failed to load driver: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $driver = $this->driverService->getDriver($id);
            return view('drivers.edit', compact('driver'));
        } catch (\Exception $e) {
            return redirect()->route('drivers.index')->with('error', 'Failed to load driver: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'driverLatinFirstName' => 'required|string|max:255',
            'driverLatinLastName' => 'required|string|max:255',
            'driverDateOfBirth' => 'required|date|date_format:Y-m-d',
            'driverLicenseNumber' => 'required|string|max:50',
            'driverDocumentType' => 'required|string|in:IDCARD,PASSPORT,DRIVINGLICENSE',
            'driverDocumentNumber' => 'required|string|max:50',
            'driverDocumentIssuingCountry' => 'required|string|size:2',
            'driverAddressStreet' => 'required|string|max:255',
            'driverAddressPostCode' => 'required|string|max:20',
            'driverAddressCity' => 'required|string|max:100',
            'driverAddressCountry' => 'required|string|size:2',
            'driverContractStartDate' => 'required|date|date_format:Y-m-d',
            'driverApplicableLaw' => 'required|string|size:2',
        ]);

        // Add the driver ID to the validated data for the API
        $validated['driverId'] = $id;

        try {
            $driver = $this->driverService->updateDriver($id, $validated);
            return redirect()->route('drivers.show', $id)->with('success', 'Driver updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update driver: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->driverService->deleteDriver($id);
            return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete driver: ' . $e->getMessage());
        }
    }

    /**
     * Get declarations for a specific driver by matching driver name
     */
    private function getDriverDeclarations(array $driver): array
    {
        try {
            // Get all declarations using pagination
            $allDeclarations = [];
            $startKey = null;

            do {
                $declarations = $this->declarationService->getDeclarationsPaginated(250, $startKey);
                $currentDeclarations = $declarations['items'] ?? $declarations ?? [];

                // Add current batch to all declarations
                $allDeclarations = array_merge($allDeclarations, $currentDeclarations);

                // Check if there are more pages
                $startKey = $declarations['lastEvaluatedKey'] ?? null;

            } while ($startKey);

            $declarationsData = $allDeclarations;

            $driverFullName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
            $driverDateOfBirth = $driver['driverDateOfBirth'] ?? null;

            $matchingDeclarations = [];

            foreach ($declarationsData as $declaration) {
                $declarationDriverName = $declaration['driverLatinFullName'] ?? null;
                $declarationDateOfBirth = $declaration['driverDateOfBirth'] ?? null;

                // Match by driver name and optionally by date of birth for better accuracy
                if ($declarationDriverName === $driverFullName) {
                    // If date of birth is available in both, use it for additional verification
                    if ($driverDateOfBirth && $declarationDateOfBirth) {
                        if ($driverDateOfBirth === $declarationDateOfBirth) {
                            $matchingDeclarations[] = $declaration;
                        }
                    } else {
                        // If no date of birth available, match by name only
                        $matchingDeclarations[] = $declaration;
                       
                    }
                }
            }

           

            // Sort declarations by creation date (newest first)
            usort($matchingDeclarations, function($a, $b) {
                $dateA = $a['declarationStartDate'] ?? '0000-00-00';
                $dateB = $b['declarationStartDate'] ?? '0000-00-00';
                return strcmp($dateB, $dateA);
            });

            return $matchingDeclarations;
        } catch (\Exception $e) {
          
            return [];
        }
    }
}
