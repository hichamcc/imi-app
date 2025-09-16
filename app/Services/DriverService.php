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
}