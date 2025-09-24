<?php

namespace App\Services;

class DriverService
{
    protected PostingApiService $apiService;

    public function __construct(PostingApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Get all drivers with optional filtering
     */
    public function getDrivers(array $filters = []): array
    {
        $endpoint = config('posting.endpoints.drivers');
        return $this->apiService->get($endpoint, $filters);
    }

    /**
     * Get a specific driver by ID
     */
    public function getDriver(string $driverId): array
    {
        $endpoint = config('posting.endpoints.drivers') . '/' . $driverId;
        return $this->apiService->get($endpoint);
    }

    /**
     * Create a new driver
     */
    public function createDriver(array $driverData): array
    {
        $endpoint = config('posting.endpoints.drivers');
        return $this->apiService->post($endpoint, $driverData);
    }

    /**
     * Update an existing driver
     */
    public function updateDriver(string $driverId, array $driverData): array
    {
        $endpoint = config('posting.endpoints.drivers') . '/' . $driverId;
        return $this->apiService->put($endpoint, $driverData);
    }

    /**
     * Delete a driver
     */
    public function deleteDriver(string $driverId): array
    {
        $endpoint = config('posting.endpoints.drivers') . '/' . $driverId;
        return $this->apiService->delete($endpoint);
    }

    /**
     * Search drivers by name or other criteria
     */
    public function searchDrivers(string $query, array $additionalFilters = []): array
    {
        $filters = array_merge(['search' => $query], $additionalFilters);
        return $this->getDrivers($filters);
    }

    /**
     * Get drivers with pagination
     */
    public function getDriversPaginated(int $limit = 50, string $startKey = null, array $filters = []): array
    {
        $params = array_merge($filters, [
            'limit' => min($limit, 250) // API max is 250
        ]);

        if ($startKey) {
            $params['startKey'] = $startKey;
        }

        return $this->getDrivers($params);
    }

    /**
     * Get active declaration countries for drivers
     */
    public function getDriversWithActiveCountries(array $drivers): array
    {
        // Get active declarations
        $declarationEndpoint = config('posting.endpoints.declarations');
        try {
            // Fetch all declarations using pagination
            $allDeclarations = [];
            $startKey = null;

            do {
                $params = ['limit' => 250];
                if ($startKey) {
                    $params['startKey'] = $startKey;
                }

                $declarations = $this->apiService->get($declarationEndpoint, $params);
                $currentDeclarations = $declarations['items'] ?? $declarations ?? [];

                // Add current batch to all declarations
                $allDeclarations = array_merge($allDeclarations, $currentDeclarations);

                // Check if there are more pages
                $startKey = $declarations['lastEvaluatedKey'] ?? null;

                \Log::info('Fetching declarations batch', [
                    'current_batch_size' => count($currentDeclarations),
                    'total_fetched' => count($allDeclarations),
                    'has_next_page' => !empty($startKey)
                ]);

            } while ($startKey);

            $declarationsData = $allDeclarations;


            // Group declarations by driver ID and collect unique countries
            $driverCountries = [];

            // Log available driver names for debugging
            $driverNames = [];
            foreach ($drivers as $driver) {
                $driverName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
                $driverNames[] = [
                    'id' => $driver['driverId'] ?? 'no-id',
                    'name' => $driverName,
                    'firstName' => $driver['driverLatinFirstName'] ?? 'no-first',
                    'lastName' => $driver['driverLatinLastName'] ?? 'no-last'
                ];
            }

            \Log::info('Available drivers for matching', [
                'driver_names' => $driverNames,
                'driver_count' => count($drivers)
            ]);

            // Log declaration names for debugging
            $declarationNames = [];
            $submittedCount = 0;
            foreach ($declarationsData as $declaration) {
                $status = $declaration['declarationStatus'] ?? null;
                if ($status === 'SUBMITTED') {
                    $submittedCount++;
                    $declarationNames[] = [
                        'fullName' => $declaration['driverLatinFullName'] ?? 'no-name',
                        'country' => $declaration['declarationPostingCountry'] ?? 'no-country',
                        'dateOfBirth' => $declaration['driverDateOfBirth'] ?? 'no-dob'
                    ];
                }
            }

            \Log::info('Submitted declarations for matching', [
                'submitted_count' => $submittedCount,
                'declaration_names' => array_slice($declarationNames, 0, 10) // First 10 for debugging
            ]);

            foreach ($declarationsData as $declaration) {
                $driverFullName = $declaration['driverLatinFullName'] ?? null;
                $country = $declaration['declarationPostingCountry'] ?? null;
                $status = $declaration['declarationStatus'] ?? null;
                $declarationDateOfBirth = $declaration['driverDateOfBirth'] ?? null;

                // Only include submitted declarations
                if ($status === 'SUBMITTED' && $driverFullName && $country) {
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
                        if (!isset($driverCountries[$matchingDriverId])) {
                            $driverCountries[$matchingDriverId] = [];
                        }
                        $driverCountries[$matchingDriverId][] = $country;

                        \Log::info('Driver matched with declaration', [
                            'driver_id' => $matchingDriverId,
                            'driver_name' => trim(($matchedDriver['driverLatinFirstName'] ?? '') . ' ' . ($matchedDriver['driverLatinLastName'] ?? '')),
                            'declaration_name' => $driverFullName,
                            'country' => $country
                        ]);
                    } else {
                        \Log::warning('No driver match found for declaration', [
                            'declaration_name' => $driverFullName,
                            'country' => $country,
                            'available_drivers_count' => count($drivers)
                        ]);
                    }
                }
            }



            // Remove duplicates and add to drivers
            foreach ($drivers as &$driver) {
                $driverId = $driver['driverId'] ?? null;
                if ($driverId && isset($driverCountries[$driverId])) {
                    $driver['activeCountries'] = array_unique($driverCountries[$driverId]);
                } else {
                    $driver['activeCountries'] = [];
                }
            }

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to fetch declarations for countries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // If declarations fetch fails, add empty countries to all drivers
            foreach ($drivers as &$driver) {
                $driver['activeCountries'] = [];
            }
        }

        return $drivers;
    }
}