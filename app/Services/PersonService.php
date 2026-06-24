<?php

namespace App\Services;

use App\Models\Person;

class PersonService
{
    public function __construct(protected DriverService $driverService) {}

    /**
     * Map a Person to the IMI/RTPD driver API payload.
     */
    public function toImiDriverPayload(Person $person): array
    {
        return [
            'driverLatinFirstName' => $person->first_name,
            'driverLatinLastName' => $person->last_name,
            'driverDateOfBirth' => $person->date_of_birth?->format('Y-m-d'),
            'driverLicenseNumber' => $person->license_number,
            'driverDocumentType' => $person->document_type,
            'driverDocumentNumber' => $person->document_number,
            'driverDocumentIssuingCountry' => $person->document_issuing_country,
            'driverAddressStreet' => $person->address_street,
            'driverAddressPostCode' => $person->address_post_code,
            'driverAddressCity' => $person->address_city,
            'driverAddressCountry' => $person->address_country,
            'driverContractStartDate' => $person->contract_start_date?->format('Y-m-d'),
            'driverApplicableLaw' => $person->applicable_law ?: auth()->user()?->applicable_law,
        ];
    }

    /**
     * Validate that a Person has every field required by the IMI driver API.
     * Returns an array of missing-field labels (empty if all present).
     */
    public function missingImiFields(Person $person): array
    {
        $payload = $this->toImiDriverPayload($person);
        $required = [
            'driverLatinFirstName' => 'First Name',
            'driverLatinLastName' => 'Last Name',
            'driverDateOfBirth' => 'Date of Birth',
            'driverLicenseNumber' => 'License Number',
            'driverDocumentType' => 'Document Type',
            'driverDocumentNumber' => 'Document Number',
            'driverDocumentIssuingCountry' => 'Issuing Country',
            'driverAddressStreet' => 'Street Address',
            'driverAddressPostCode' => 'Post Code',
            'driverAddressCity' => 'City',
            'driverAddressCountry' => 'Country',
            'driverContractStartDate' => 'Contract Start Date',
            'driverApplicableLaw' => 'Applicable Law',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (empty($payload[$field])) {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    /**
     * Build a Person attribute array from an IMI driver API response.
     */
    public function fromImiDriver(array $driver): array
    {
        return [
            'first_name' => $driver['driverLatinFirstName'] ?? '',
            'last_name' => $driver['driverLatinLastName'] ?? '',
            'date_of_birth' => !empty($driver['driverDateOfBirth']) ? $driver['driverDateOfBirth'] : null,
            'document_type' => $driver['driverDocumentType'] ?? null,
            'document_number' => $driver['driverDocumentNumber'] ?? null,
            'document_issuing_country' => $driver['driverDocumentIssuingCountry'] ?? null,
            'license_number' => $driver['driverLicenseNumber'] ?? null,
            'address_street' => $driver['driverAddressStreet'] ?? null,
            'address_post_code' => $driver['driverAddressPostCode'] ?? null,
            'address_city' => $driver['driverAddressCity'] ?? null,
            'address_country' => $driver['driverAddressCountry'] ?? null,
            'contract_start_date' => !empty($driver['driverContractStartDate']) ? $driver['driverContractStartDate'] : null,
            'applicable_law' => $driver['driverApplicableLaw'] ?? null,
        ];
    }

    /**
     * Import an existing IMI driver into the local persons table.
     * If a Person already exists with that imi_driver_id under this user, returns that one.
     */
    public function importFromImi(string $driverId): array
    {
        $userId = auth()->id();

        // Skip if already imported for this user
        $existing = Person::where('user_id', $userId)->where('imi_driver_id', $driverId)->first();
        if ($existing) {
            return ['success' => true, 'person' => $existing, 'created' => false, 'error' => null];
        }

        try {
            $driver = $this->driverService->getDriver($driverId);

            $attrs = $this->fromImiDriver($driver);
            $attrs['user_id'] = $userId;
            $attrs['imi_driver_id'] = $driverId;
            $attrs['imi_user_id'] = $userId;
            $attrs['position'] = 'Driver';

            $person = Person::create($attrs);

            return ['success' => true, 'person' => $person, 'created' => true, 'error' => null];
        } catch (\Throwable $e) {
            \Log::warning('IMI → Person import failed', ['driver_id' => $driverId, 'error' => $e->getMessage()]);
            return ['success' => false, 'person' => null, 'created' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Push the person to IMI as a driver under the currently authenticated user's
     * credentials. Returns ['success' => bool, 'driver_id' => string|null, 'error' => string|null].
     */
    public function syncToImi(Person $person): array
    {
        if ($person->imi_driver_id) {
            return ['success' => true, 'driver_id' => $person->imi_driver_id, 'error' => null];
        }

        $missing = $this->missingImiFields($person);
        if (!empty($missing)) {
            return [
                'success' => false,
                'driver_id' => null,
                'error' => 'Cannot sync to IMI — missing required fields: ' . implode(', ', $missing),
            ];
        }

        try {
            $payload = $this->toImiDriverPayload($person);
            $response = $this->driverService->createDriver($payload);

            $driverId = $response['driverId'] ?? null;
            if (!$driverId) {
                return ['success' => false, 'driver_id' => null, 'error' => 'IMI API did not return a driverId.'];
            }

            $person->update([
                'imi_driver_id' => $driverId,
                'imi_user_id' => auth()->id(),
            ]);

            return ['success' => true, 'driver_id' => $driverId, 'error' => null];
        } catch (\Throwable $e) {
            \Log::warning('Person → IMI sync failed', ['person_id' => $person->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'driver_id' => null, 'error' => $e->getMessage()];
        }
    }
}
