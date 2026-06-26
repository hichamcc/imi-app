<?php

namespace App\Services;

/**
 * Wraps the RTPD /plate-numbers (vehicle register) endpoints.
 *
 * Schema (CreatePlateNumberModelPublic):
 *   plateNumber          required, string
 *   registrationCountry  required, 2-letter ISO country
 *   transportType        required, CARRIAGE_OF_GOODS | CARRIAGE_OF_PASSENGERS
 *   vehicleWeight        optional, LIGHT | HEAVY | ''  (use '' for passenger vehicles)
 *
 * Local Truck → API mapping:
 *   plate                 → plateNumber
 *   registration_country  → registrationCountry
 *   carriage_type         → transportType
 *   weight_type ('N/A')   → vehicleWeight ('')
 *   weight_type LIGHT/HEAVY → vehicleWeight LIGHT/HEAVY
 */
class PlateNumberService
{
    public function __construct(protected PostingApiService $apiService) {}

    public function list(array $filters = []): array
    {
        return $this->apiService->get(config('posting.endpoints.plate_numbers'), $filters);
    }

    public function paginated(int $limit = 250, ?string $startKey = null): array
    {
        $params = ['limit' => min($limit, 250)];
        if ($startKey) $params['startKey'] = $startKey;
        return $this->list($params);
    }

    /**
     * Fetch every plate-number across all API pages.
     */
    public function all(): array
    {
        $items = [];
        $startKey = null;
        do {
            $page = $this->paginated(250, $startKey);
            $items = array_merge($items, $page['items'] ?? []);
            $startKey = $page['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return $items;
    }

    public function get(string $id): array
    {
        return $this->apiService->get(config('posting.endpoints.plate_numbers') . '/' . $id);
    }

    public function create(array $data): array
    {
        return $this->apiService->post(config('posting.endpoints.plate_numbers'), $data);
    }

    public function update(string $id, array $data): array
    {
        return $this->apiService->put(config('posting.endpoints.plate_numbers') . '/' . $id, $data);
    }

    public function delete(string $id): array
    {
        return $this->apiService->delete(config('posting.endpoints.plate_numbers') . '/' . $id);
    }

    /**
     * Convert a local Truck row into the API payload shape.
     */
    public function payloadFromTruck(\App\Models\Truck $truck): array
    {
        $carriage = $truck->carriage_type ?: \App\Models\Truck::CARRIAGE_GOODS;
        $weight = match (true) {
            $carriage === \App\Models\Truck::CARRIAGE_PASSENGERS => '',
            $truck->weight_type === \App\Models\Truck::WEIGHT_LIGHT => 'LIGHT',
            default => 'HEAVY',
        };

        return [
            'plateNumber' => $truck->plate,
            'registrationCountry' => strtoupper((string) $truck->registration_country),
            'transportType' => $carriage,
            'vehicleWeight' => $weight,
        ];
    }
}
