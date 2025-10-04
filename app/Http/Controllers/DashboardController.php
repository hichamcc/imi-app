<?php

namespace App\Http\Controllers;

use App\Services\DriverService;
use App\Services\DeclarationService;
use App\Services\TruckService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DriverService $driverService;
    protected DeclarationService $declarationService;
    protected TruckService $truckService;

    public function __construct(DriverService $driverService, DeclarationService $declarationService, TruckService $truckService)
    {
        $this->driverService = $driverService;
        $this->declarationService = $declarationService;
        $this->truckService = $truckService;
    }

    /**
     * Display the dashboard with statistics
     */
    public function index()
    {
        // Get users this user can impersonate (works regardless of API credentials)
        $impersonatableUsers = auth()->user()->getImpersonatableUsers();

        // Check if user is admin and show impersonation dashboard
        if (auth()->user()->isAdmin()) {
            $users = \App\Models\User::where('is_admin', false)->active()->get();
            $groups = \App\Models\UserGroup::with(['users', 'creator'])->get();
            return view('dashboard', compact('users', 'groups', 'impersonatableUsers'));
        }

        try {
            // Fetch drivers to calculate statistics
            $drivers = $this->driverService->getDriversPaginated(50);

            // Use the total count from API response, fall back to items count
            $totalDrivers = $drivers['count'] ?? count($drivers['items'] ?? []);
            $activeDeclarations = 0;
            $driversWithDeclarations = 0;

            // Calculate statistics from drivers data
            if (isset($drivers['items']) && is_array($drivers['items'])) {
                foreach ($drivers['items'] as $driver) {
                    if ($driver['driverHasDeclarations'] ?? false) {
                        $driversWithDeclarations++;
                        $activeDeclarations += $driver['driverCountActiveDeclarations'] ?? 0;
                    }
                }
            }

            // Fetch declaration statistics
            $declarationStats = $this->getDeclarationStatistics();

            // Fetch truck statistics
            $truckStats = $this->truckService->getFleetStatistics();

            $stats = [
                'totalDrivers' => $totalDrivers,
                'activeDeclarations' => $activeDeclarations,
                'driversWithDeclarations' => $driversWithDeclarations,
                'pendingSubmissions' => $declarationStats['draft'] ?? 0,
                'totalDeclarations' => $declarationStats['total'] ?? 0,
                'submittedDeclarations' => $declarationStats['submitted'] ?? 0,
                'withdrawnDeclarations' => $declarationStats['withdrawn'] ?? 0,
                'expiredDeclarations' => $declarationStats['expired'] ?? 0,
                'totalTrucks' => $truckStats['total'] ?? 0,
                'availableTrucks' => $truckStats['available'] ?? 0,
                'trucksInTransit' => $truckStats['in_transit'] ?? 0,
                'trucksInMaintenance' => $truckStats['maintenance'] ?? 0,
                'retiredTrucks' => $truckStats['retired'] ?? 0,
                'totalCapacity' => $truckStats['total_capacity'] ?? 0,
                'fleetUtilization' => $truckStats['utilization_percentage'] ?? 0,
            ];

            return view('dashboard', compact('stats', 'impersonatableUsers'));

        } catch (\Exception $e) {
            // Log the exception
            \Log::error('Dashboard exception for user ' . auth()->user()->name . ': ' . $e->getMessage());

            // Fallback to placeholder values if API fails
            $stats = [
                'totalDrivers' => '--',
                'activeDeclarations' => '--',
                'driversWithDeclarations' => '--',
                'pendingSubmissions' => '--',
                'totalDeclarations' => '--',
                'submittedDeclarations' => '--',
                'withdrawnDeclarations' => '--',
                'expiredDeclarations' => '--',
                'totalTrucks' => '--',
                'availableTrucks' => '--',
                'trucksInTransit' => '--',
                'trucksInMaintenance' => '--',
                'retiredTrucks' => '--',
                'totalCapacity' => '--',
                'fleetUtilization' => '--',
            ];

            return view('dashboard', compact('stats', 'impersonatableUsers'))->with('error', 'Failed to load statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get declaration statistics by status
     */
    private function getDeclarationStatistics()
    {
        $stats = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'withdrawn' => 0,
            'expired' => 0,
        ];

        try {
            // Fetch declarations to count by status
            $declarations = $this->declarationService->getDeclarationsPaginated(100);

            if (isset($declarations['items']) && is_array($declarations['items'])) {
                $stats['total'] = count($declarations['items']);

                foreach ($declarations['items'] as $declaration) {
                    $status = strtolower($declaration['declarationStatus'] ?? '');
                    if (isset($stats[$status])) {
                        $stats[$status]++;
                    }
                }
            }
        } catch (\Exception $e) {
            // If API fails, return zeros
        }

        return $stats;
    }
}