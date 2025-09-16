<?php

namespace App\Http\Controllers;

use App\Services\DriverService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected DriverService $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
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
            $drivers = $this->driverService->getDriversPaginated($limit, $startKey, $filters);
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
            $driver = $this->driverService->getDriver($id);
            return view('drivers.show', compact('driver'));
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
}
