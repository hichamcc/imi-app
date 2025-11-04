<?php

namespace App\Http\Controllers;

use App\Models\DriverProfile;
use App\Services\DriverService;
use Illuminate\Http\Request;

class DriverProfileController extends Controller
{
    protected DriverService $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    /**
     * Update driver email
     */
    public function updateEmail(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|string',
            'email' => 'required|email|max:255',
        ]);

        try {
            // Verify driver exists in API
            $driver = $this->driverService->getDriver($request->driver_id);

            // Get or create driver profile (globally unique by driver_id)
            $profile = DriverProfile::getOrCreateForDriver($request->driver_id);

            // Update email
            $profile->updateEmail($request->email);

            return response()->json([
                'success' => true,
                'message' => 'Driver email updated successfully',
                'driver_name' => $driver['driverLatinFullName'] ?? 'Unknown',
                'email' => $request->email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update driver email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver profile data
     */
    public function show($driverId)
    {
        try {
            $profile = DriverProfile::where('driver_id', $driverId)->first();

            return response()->json([
                'success' => true,
                'profile' => $profile,
                'email' => $profile?->email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get driver profile'
            ], 500);
        }
    }

    /**
     * Bulk update driver emails
     */
    public function bulkUpdateEmails(Request $request)
    {
        $request->validate([
            'drivers' => 'required|array',
            'drivers.*.driver_id' => 'required|string',
            'drivers.*.email' => 'required|email|max:255',
        ]);

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($request->drivers as $driverData) {
            try {
                // Get or create driver profile (globally unique)
                $profile = DriverProfile::getOrCreateForDriver($driverData['driver_id']);

                // Update email
                $profile->updateEmail($driverData['email']);

                $results[] = [
                    'driver_id' => $driverData['driver_id'],
                    'success' => true,
                    'email' => $driverData['email']
                ];
                $successCount++;

            } catch (\Exception $e) {
                $results[] = [
                    'driver_id' => $driverData['driver_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $errorCount++;
            }
        }

        return response()->json([
            'success' => $errorCount === 0,
            'message' => "Updated {$successCount} driver emails, {$errorCount} errors",
            'results' => $results,
            'summary' => [
                'total' => count($request->drivers),
                'success' => $successCount,
                'errors' => $errorCount
            ]
        ]);
    }
}