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
            // Fetch ALL drivers using pagination to get accurate counts
            $allDrivers = $this->getAllDrivers();
            $totalDrivers = count($allDrivers);

            // Fetch ALL declarations to get accurate counts
            $allDeclarations = $this->getAllDeclarations();

            // Calculate driver statistics by matching with declarations
            $driverStats = $this->calculateDriverStatistics($allDrivers, $allDeclarations);

            // Calculate declaration statistics
            $declarationStats = $this->calculateDeclarationStatistics($allDeclarations);

            // Fetch truck statistics
            $truckStats = $this->truckService->getFleetStatistics();

            $stats = [
                'totalDrivers' => $totalDrivers,
                'activeDeclarations' => $driverStats['activeDeclarations'],
                'driversWithDeclarations' => $driverStats['driversWithDeclarations'],
                'pendingSubmissions' => $declarationStats['draft'],
                'totalDeclarations' => $declarationStats['total'],
                'submittedDeclarations' => $declarationStats['submitted'],
                'withdrawnDeclarations' => $declarationStats['withdrawn'],
                'expiredDeclarations' => $declarationStats['expired'],
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
     * Get ALL drivers using pagination
     */
    private function getAllDrivers()
    {
        $allDrivers = [];
        $startKey = null;

        do {
            $drivers = $this->driverService->getDriversPaginated(250, $startKey);
            $currentDrivers = $drivers['items'] ?? [];

            $allDrivers = array_merge($allDrivers, $currentDrivers);
            $startKey = $drivers['lastEvaluatedKey'] ?? null;

        } while ($startKey);

        return $allDrivers;
    }

    /**
     * Get ALL declarations using pagination
     */
    private function getAllDeclarations()
    {
        $allDeclarations = [];
        $startKey = null;

        do {
            $declarations = $this->declarationService->getDeclarationsPaginated(250, $startKey);
            $currentDeclarations = $declarations['items'] ?? [];

            $allDeclarations = array_merge($allDeclarations, $currentDeclarations);
            $startKey = $declarations['lastEvaluatedKey'] ?? null;

        } while ($startKey);

        return $allDeclarations;
    }

    /**
     * Calculate driver statistics by matching with declarations
     */
    private function calculateDriverStatistics($drivers, $declarations)
    {
        $driversWithDeclarations = 0;
        $activeDeclarations = 0;

        // Group declarations by driver name and date of birth
        $declarationsByDriver = [];
        foreach ($declarations as $declaration) {
            $driverName = $declaration['driverLatinFullName'] ?? null;
            $driverDob = $declaration['driverDateOfBirth'] ?? null;
            $status = $declaration['declarationStatus'] ?? null;

            if ($driverName && $status === 'SUBMITTED') {
                $key = $driverName . '|' . $driverDob;
                if (!isset($declarationsByDriver[$key])) {
                    $declarationsByDriver[$key] = 0;
                }
                $declarationsByDriver[$key]++;
            }
        }

        // Match drivers with their declarations
        foreach ($drivers as $driver) {
            $driverName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
            $driverDob = $driver['driverDateOfBirth'] ?? null;
            $key = $driverName . '|' . $driverDob;

            if (isset($declarationsByDriver[$key]) && $declarationsByDriver[$key] > 0) {
                $driversWithDeclarations++;
                $activeDeclarations += $declarationsByDriver[$key];
            }
        }

        return [
            'driversWithDeclarations' => $driversWithDeclarations,
            'activeDeclarations' => $activeDeclarations
        ];
    }

    /**
     * Calculate declaration statistics by status
     */
    private function calculateDeclarationStatistics($declarations)
    {
        $stats = [
            'total' => count($declarations),
            'draft' => 0,
            'submitted' => 0,
            'withdrawn' => 0,
            'expired' => 0,
        ];

        foreach ($declarations as $declaration) {
            $status = strtolower($declaration['declarationStatus'] ?? '');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }
}