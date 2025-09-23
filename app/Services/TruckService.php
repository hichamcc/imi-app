<?php

namespace App\Services;

use App\Models\Truck;
use App\Models\TruckAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TruckService
{
    protected DriverService $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    /**
     * Get all trucks with pagination and optional filtering
     */
    public function getTrucksPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Truck::with('activeAssignments')
            ->where('user_id', auth()->id());

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('plate', 'like', "%{$search}%");
            });
        }

        $trucks = $query->orderBy('name')->paginate($perPage);

        // Add driver names to active assignments for each truck
        foreach ($trucks as $truck) {
            foreach ($truck->activeAssignments as $assignment) {
                try {
                    $driverData = $this->driverService->getDriver($assignment->driver_id);
                    $assignment->driver_name = trim(($driverData['driverLatinFirstName'] ?? '') . ' ' . ($driverData['driverLatinLastName'] ?? ''));
                } catch (\Exception $e) {
                    $assignment->driver_name = 'Unknown Driver';
                }
            }
        }

        return $trucks;
    }

    /**
     * Get a single truck with its assignments and driver details
     */
    public function getTruck(int $id): Truck
    {
        $truck = Truck::with(['activeAssignments', 'assignments' => function ($query) {
            $query->orderBy('assigned_date', 'desc');
        }])
        ->where('user_id', auth()->id())
        ->findOrFail($id);

        // Fetch driver details for active assignments
        foreach ($truck->activeAssignments as $assignment) {
            try {
                $driverData = $this->driverService->getDriver($assignment->driver_id);
                $assignment->driver_name = trim(($driverData['driverLatinFirstName'] ?? '') . ' ' . ($driverData['driverLatinLastName'] ?? ''));
                $assignment->driver_details = $driverData;
            } catch (\Exception $e) {
                $assignment->driver_name = 'Driver not found';
                $assignment->driver_details = null;
            }
        }

        // Fetch driver details for all assignments (for history)
        foreach ($truck->assignments as $assignment) {
            if (!isset($assignment->driver_name)) {
                try {
                    $driverData = $this->driverService->getDriver($assignment->driver_id);
                    $assignment->driver_name = trim(($driverData['driverLatinFirstName'] ?? '') . ' ' . ($driverData['driverLatinLastName'] ?? ''));
                    $assignment->driver_details = $driverData;
                } catch (\Exception $e) {
                    $assignment->driver_name = 'Driver not found';
                    $assignment->driver_details = null;
                }
            }
        }

        return $truck;
    }

    /**
     * Create a new truck
     */
    public function createTruck(array $data): Truck
    {
        $data['user_id'] = auth()->id();
        return Truck::create($data);
    }

    /**
     * Update a truck
     */
    public function updateTruck(int $id, array $data): Truck
    {
        $truck = Truck::where('user_id', auth()->id())->findOrFail($id);
        $truck->update($data);
        return $truck;
    }

    /**
     * Delete a truck
     */
    public function deleteTruck(int $id): bool
    {
        $truck = Truck::where('user_id', auth()->id())->findOrFail($id);

        // Check if truck has active assignments
        if ($truck->activeAssignments()->exists()) {
            throw new \Exception('Cannot delete truck with active driver assignments.');
        }

        return $truck->delete();
    }

    /**
     * Assign a driver to a truck
     */
    public function assignDriver(int $truckId, string $driverId, ?string $notes = null): TruckAssignment
    {
        $truck = Truck::where('user_id', auth()->id())->findOrFail($truckId);

        // Check if truck can be assigned
        if (!$truck->canBeAssigned()) {
            throw new \Exception('Truck is not available for assignment. Status: ' . $truck->status);
        }

        // Validate driver exists via API
        try {
            $this->driverService->getDriver($driverId);
        } catch (\Exception $e) {
            throw new \Exception('Driver not found or invalid: ' . $e->getMessage());
        }

        // Check if driver is already assigned to this truck
        $existingAssignment = TruckAssignment::where('truck_id', $truckId)
            ->where('driver_id', $driverId)
            ->where('is_active', true)
            ->first();

        if ($existingAssignment) {
            throw new \Exception('Driver is already assigned to this truck.');
        }

        return TruckAssignment::create([
            'truck_id' => $truckId,
            'driver_id' => $driverId,
            'assigned_date' => now()->toDateString(),
            'is_active' => true,
            'notes' => $notes,
        ]);
    }

    /**
     * Unassign a driver from a truck
     */
    public function unassignDriver(int $assignmentId): TruckAssignment
    {
        $assignment = TruckAssignment::findOrFail($assignmentId);

        $assignment->update([
            'is_active' => false,
            'unassigned_date' => now()->toDateString(),
        ]);

        return $assignment;
    }

    /**
     * Get trucks assigned to a specific driver
     */
    public function getTrucksForDriver(string $driverId): Collection
    {
        return Truck::where('user_id', auth()->id())
            ->whereHas('activeAssignments', function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            })->get();
    }

    /**
     * Get fleet statistics
     */
    public function getFleetStatistics(): array
    {
        $userTrucks = Truck::where('user_id', auth()->id());

        $stats = [
            'total' => $userTrucks->count(),
            'available' => $userTrucks->where('status', 'Available')->count(),
            'in_transit' => $userTrucks->where('status', 'In-Transit')->count(),
            'maintenance' => $userTrucks->where('status', 'Maintenance')->count(),
            'retired' => $userTrucks->where('status', 'Retired')->count(),
            'total_capacity' => $userTrucks->sum('capacity_tons'),
            'active_assignments' => TruckAssignment::whereIn('truck_id',
                Truck::where('user_id', auth()->id())->pluck('id')
            )->where('is_active', true)->count(),
        ];

        // Calculate utilization (trucks with active assignments / total available trucks)
        $availableForAssignment = Truck::where('user_id', auth()->id())
            ->whereIn('status', ['Available', 'In-Transit'])->count();
        $assignedTrucks = Truck::where('user_id', auth()->id())
            ->whereHas('activeAssignments')->count();

        $stats['utilization_percentage'] = $availableForAssignment > 0
            ? round(($assignedTrucks / $availableForAssignment) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get assignment report
     */
    public function getAssignmentReport(): Collection
    {
        return TruckAssignment::with(['truck'])
            ->whereIn('truck_id', Truck::where('user_id', auth()->id())->pluck('id'))
            ->where('is_active', true)
            ->orderBy('assigned_date', 'desc')
            ->get();
    }

    /**
     * Get available trucks for assignment (not retired or in maintenance)
     */
    public function getAvailableTrucks(): Collection
    {
        return Truck::where('user_id', auth()->id())
            ->whereIn('status', ['Available', 'In-Transit'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get truck plates for a driver (for declaration integration)
     */
    public function getTruckPlatesForDriver(string $driverId): array
    {
        return $this->getTrucksForDriver($driverId)
            ->pluck('plate')
            ->toArray();
    }
}