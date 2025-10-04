<?php

namespace App\Services;

class DeclarationService
{
    protected PostingApiService $apiService;

    public function __construct(PostingApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Get all declarations with optional filtering
     */
    public function getDeclarations(array $filters = []): array
    {
        $endpoint = config('posting.endpoints.declarations');
        return $this->apiService->get($endpoint, $filters);
    }

    /**
     * Get a specific declaration by ID
     */
    public function getDeclaration(string $declarationId): array
    {
        $endpoint = config('posting.endpoints.declarations') . '/' . $declarationId;
        return $this->apiService->get($endpoint);
    }

    /**
     * Create a new declaration
     */
    public function createDeclaration(array $declarationData): array
    {
        $endpoint = config('posting.endpoints.declarations');
        return $this->apiService->post($endpoint, $declarationData);
    }

    /**
     * Update an existing declaration
     */
    public function updateDeclaration(string $declarationId, array $declarationData): array
    {
        $endpoint = config('posting.endpoints.declarations') . '/' . $declarationId;
        return $this->apiService->put($endpoint, $declarationData);
    }

    /**
     * Delete a declaration
     */
    public function deleteDeclaration(string $declarationId): array
    {
        $endpoint = config('posting.endpoints.declarations') . '/' . $declarationId;
        return $this->apiService->delete($endpoint);
    }

    /**
     * Submit a declaration
     */
    public function submitDeclaration(string $declarationId): array
    {
        $endpoint = config('posting.endpoints.declarations') . '/' . $declarationId . '/submit';
        return $this->apiService->post($endpoint);
    }

    /**
     * Update a submitted declaration
     */
    public function updateSubmittedDeclaration(string $declarationId, array $declarationData): array
    {
        $endpoint = config('posting.endpoints.declarations') . '/' . $declarationId . '/submit';
        return $this->apiService->put($endpoint, $declarationData);
    }

    /**
     * Withdraw a declaration
     */
    public function withdrawDeclaration(string $declarationId): array
    {
        $endpoint = config('posting.endpoints.declarations') . '/' . $declarationId . '/withdraw';
        return $this->apiService->post($endpoint);
    }

    /**
     * Email declaration
     */
    public function emailDeclaration(string $declarationId, string $emailAddress, string $language): array
    {
        $endpoint = "/declarations/{$declarationId}/email";

        $data = [
            'email' => $emailAddress,
            'language' => $language
        ];

        return $this->apiService->post($endpoint, $data);
    }

    /**
     * Print declaration (generate PDF)
     */
    public function printDeclaration(string $declarationId, string $language): array
    {
        $endpoint = "/declarations/{$declarationId}/print";

        $data = [
            'declarationLanguage' => $language
        ];

        return $this->apiService->post($endpoint, $data);
    }

    /**
     * Get declarations by status
     */
    public function getDeclarationsByStatus(string $status, array $additionalFilters = []): array
    {
        $filters = array_merge(['status' => $status], $additionalFilters);
        return $this->getDeclarations($filters);
    }

    /**
     * Get declarations for a specific driver
     */
    public function getDeclarationsByDriver(string $driverId, array $additionalFilters = []): array
    {
        $filters = array_merge(['driverId' => $driverId], $additionalFilters);
        return $this->getDeclarations($filters);
    }

    /**
     * Get declarations with pagination
     */
    public function getDeclarationsPaginated(int $limit = 50, string $startKey = null, array $filters = []): array
    {
        $params = array_merge($filters, [
            'limit' => min($limit, 250) // API max is 250
        ]);

        if ($startKey) {
            $params['startKey'] = $startKey;
        }

        return $this->getDeclarations($params);
    }

    /**
     * Get draft declarations
     */
    public function getDraftDeclarations(array $additionalFilters = []): array
    {
        return $this->getDeclarationsByStatus('draft', $additionalFilters);
    }

    /**
     * Get submitted declarations
     */
    public function getSubmittedDeclarations(array $additionalFilters = []): array
    {
        return $this->getDeclarationsByStatus('submitted', $additionalFilters);
    }

    /**
     * Get withdrawn declarations
     */
    public function getWithdrawnDeclarations(array $additionalFilters = []): array
    {
        return $this->getDeclarationsByStatus('withdrawn', $additionalFilters);
    }

    /**
     * Get declarations by posting country
     */
    public function getDeclarationsByCountry(string $postingCountry, array $additionalFilters = []): array
    {
        $filters = array_merge(['postingCountry' => $postingCountry], $additionalFilters);
        return $this->getDeclarations($filters);
    }

    /**
     * Search declarations by date range
     */
    public function getDeclarationsByDateRange(string $endDateFrom = null, string $endDateTo = null, array $additionalFilters = []): array
    {
        $filters = $additionalFilters;
        if ($endDateFrom) {
            $filters['endDateFrom'] = $endDateFrom;
        }
        if ($endDateTo) {
            $filters['endDateTo'] = $endDateTo;
        }

        return $this->getDeclarations($filters);
    }

    /**
     * Get available posting countries
     */
    public static function getPostingCountries(): array
    {
        return [
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'ES' => 'Spain',
            'FI' => 'Finland',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            'GR' => 'Greece',
            'HR' => 'Croatia',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IT' => 'Italy',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'SE' => 'Sweden',
            'SI' => 'Slovenia',
            'SK' => 'Slovakia'
        ];
    }

    /**
     * Get operation types
     */
    public static function getOperationTypes(): array
    {
        return [
            'CABOTAGE_OPERATIONS' => 'Cabotage Operations',
            'INTERNATIONAL_CARRIAGE' => 'International Carriage'
        ];
    }

    /**
     * Get transport types
     */
    public static function getTransportTypes(): array
    {
        return [
            'CARRIAGE_OF_GOODS' => 'Carriage of Goods',
            'CARRIAGE_OF_PASSENGERS' => 'Carriage of Passengers'
        ];
    }

    /**
     * Get declaration statuses
     */
    public static function getStatuses(): array
    {
        return [
            'DRAFT' => 'Draft',
            'SUBMITTED' => 'Submitted',
            'WITHDRAWN' => 'Withdrawn',
            'EXPIRED' => 'Expired'
        ];
    }

}