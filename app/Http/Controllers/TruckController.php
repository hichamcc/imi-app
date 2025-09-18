<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Services\TruckService;
use App\Services\DriverService;
use Illuminate\Http\Request;

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
        return view('trucks.create', compact('statuses', 'countries'));
    }

    /**
     * Store a newly created truck
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'plate' => 'required|string|max:20|unique:trucks,plate',
            'capacity_tons' => 'required|numeric|min:0.01|max:999999.99',
            'status' => 'required|in:Available,In-Transit,Maintenance,Retired',
            'countries' => 'nullable|array',
            'countries.*' => 'in:' . implode(',', array_keys(\App\Services\DeclarationService::getPostingCountries())),
        ]);

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
            return view('trucks.edit', compact('truck', 'statuses', 'countries'));
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
            'plate' => 'required|string|max:20|unique:trucks,plate,' . $id,
            'capacity_tons' => 'required|numeric|min:0.01|max:999999.99',
            'status' => 'required|in:Available,In-Transit,Maintenance,Retired',
            'countries' => 'nullable|array',
            'countries.*' => 'in:' . implode(',', array_keys(\App\Services\DeclarationService::getPostingCountries())),
        ]);

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
}
