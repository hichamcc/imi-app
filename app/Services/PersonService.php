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
