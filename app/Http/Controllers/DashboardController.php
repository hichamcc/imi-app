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

            // Check if it's a permissions issue
            $errorMessage = 'Failed to load statistics: ' . $e->getMessage();
            if (strpos($e->getMessage(), 'forbidden') !== false || strpos($e->getMessage(), 'Insufficient permissions') !== false) {
                $errorMessage = 'API access forbidden. Please contact your administrator to ensure you have the required permissions for drivers and declarations.';
            }

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

            return view('dashboard', compact('stats', 'impersonatableUsers'))->with('error', $errorMessage);
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
     * Uses the same logic as DriverService::getDriversWithActiveCountries
     */
    private function calculateDriverStatistics($drivers, $declarations)
    {
        $driversWithDeclarations = 0;
        $activeDeclarations = 0;
        $driverDeclarationCounts = [];

        // Log some debug info
        \Log::info('Dashboard: Calculating driver statistics', [
            'total_drivers' => count($drivers),
            'total_declarations' => count($declarations)
        ]);

        // Use the same matching logic as DriverService
        foreach ($declarations as $declaration) {
            $driverFullName = $declaration['driverLatinFullName'] ?? null;
            $status = $declaration['declarationStatus'] ?? null;
            $declarationDateOfBirth = $declaration['driverDateOfBirth'] ?? null;

            // Only include submitted declarations
            if ($status === 'SUBMITTED' && $driverFullName) {
                // Find driver by full name match with additional criteria to handle duplicates
                $potentialMatches = [];

                foreach ($drivers as $driver) {
                    $driverName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
                    if ($driverName === $driverFullName) {
                        $potentialMatches[] = $driver;
                    }
                }

                // If we have multiple drivers with same name, try to match by date of birth
                $matchedDriver = null;
                if (count($potentialMatches) === 1) {
                    $matchedDriver = $potentialMatches[0];
                } elseif (count($potentialMatches) > 1) {
                    if ($declarationDateOfBirth) {
                        // Try to match by date of birth for better accuracy
                        foreach ($potentialMatches as $driver) {
                            if (($driver['driverDateOfBirth'] ?? null) === $declarationDateOfBirth) {
                                $matchedDriver = $driver;
                                break;
                            }
                        }
                    }

                    // If no date of birth match, assign to first match as fallback
                    if (!$matchedDriver) {
                        $matchedDriver = $potentialMatches[0];
                    }
                }

                if ($matchedDriver) {
                    $matchingDriverId = $matchedDriver['driverId'];
                    if (!isset($driverDeclarationCounts[$matchingDriverId])) {
                        $driverDeclarationCounts[$matchingDriverId] = 0;
                    }
                    $driverDeclarationCounts[$matchingDriverId]++;
                    $activeDeclarations++;
                }
            }
        }

        // Count unique drivers with declarations
        $driversWithDeclarations = count($driverDeclarationCounts);

        \Log::info('Dashboard: Driver statistics calculated', [
            'drivers_with_declarations' => $driversWithDeclarations,
            'active_declarations' => $activeDeclarations,
            'sample_driver_counts' => array_slice($driverDeclarationCounts, 0, 3, true)
        ]);

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