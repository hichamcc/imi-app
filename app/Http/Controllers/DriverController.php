<?php

namespace App\Http\Controllers;

use App\Services\DriverService;
use App\Services\DeclarationService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected DriverService $driverService;
    protected DeclarationService $declarationService;

    public function __construct(DriverService $driverService, DeclarationService $declarationService)
    {
        $this->driverService = $driverService;
        $this->declarationService = $declarationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 50);
        $startKey = $request->get('startKey');
        $term = $request->get('term');
        $withActiveDeclarations = $request->get('withActiveDeclarations');
        $byLastName = $request->get('byLastName');
        $dateOfBirth = $request->get('dateOfBirth');

        $filters = [];
        if ($term) {
            $filters['term'] = $term;
        }
        if ($withActiveDeclarations !== null) {
            $filters['withActiveDeclarations'] = $withActiveDeclarations;
        }
        if ($byLastName !== null) {
            $filters['byLastName'] = $byLastName;
        }
        if ($dateOfBirth) {
            $filters['dateOfBirth'] = $dateOfBirth;
        }

        try {
            $drivers = $this->driverService->getDriversPaginatedWithProfiles($limit, $startKey, $filters);

            // Add active declaration countries to drivers
            if (isset($drivers['items'])) {
                $drivers['items'] = $this->driverService->getDriversWithActiveCountries($drivers['items']);
            } elseif (is_array($drivers)) {
                $drivers = $this->driverService->getDriversWithActiveCountries($drivers);
            }

            return view('drivers.index', compact('drivers', 'term', 'limit', 'startKey', 'withActiveDeclarations', 'byLastName', 'dateOfBirth'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load drivers: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('drivers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driverLatinFirstName' => 'required|string|max:255',
            'driverLatinLastName' => 'required|string|max:255',
            'driverDateOfBirth' => 'required|date|date_format:Y-m-d',
            'driverLicenseNumber' => 'required|string|max:50',
            'driverDocumentType' => 'required|string|in:IDCARD,PASSPORT,DRIVINGLICENSE',
            'driverDocumentNumber' => 'required|string|max:50',
            'driverDocumentIssuingCountry' => 'required|string|size:2',
            'driverAddressStreet' => 'required|string|max:255',
            'driverAddressPostCode' => 'required|string|max:20',
            'driverAddressCity' => 'required|string|max:100',
            'driverAddressCountry' => 'required|string|size:2',
            'driverContractStartDate' => 'required|date|date_format:Y-m-d',
            'driverApplicableLaw' => 'required|string|size:2',
        ]);

        try {
            $driver = $this->driverService->createDriver($validated);
            return redirect()->route('drivers.show', $driver['driverId'])->with('success', 'Driver created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create driver: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $driver = $this->driverService->getDriverWithProfile($id);

            // Get driver's declarations by matching driver name
            $declarations = $this->getDriverDeclarations($driver);

            return view('drivers.show', compact('driver', 'declarations'));
        } catch (\Exception $e) {
            return redirect()->route('drivers.index')->with('error', 'Failed to load driver: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $driver = $this->driverService->getDriver($id);
            return view('drivers.edit', compact('driver'));
        } catch (\Exception $e) {
            return redirect()->route('drivers.index')->with('error', 'Failed to load driver: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'driverLatinFirstName' => 'required|string|max:255',
            'driverLatinLastName' => 'required|string|max:255',
            'driverDateOfBirth' => 'required|date|date_format:Y-m-d',
            'driverLicenseNumber' => 'required|string|max:50',
            'driverDocumentType' => 'required|string|in:IDCARD,PASSPORT,DRIVINGLICENSE',
            'driverDocumentNumber' => 'required|string|max:50',
            'driverDocumentIssuingCountry' => 'required|string|size:2',
            'driverAddressStreet' => 'required|string|max:255',
            'driverAddressPostCode' => 'required|string|max:20',
            'driverAddressCity' => 'required|string|max:100',
            'driverAddressCountry' => 'required|string|size:2',
            'driverContractStartDate' => 'required|date|date_format:Y-m-d',
            'driverApplicableLaw' => 'required|string|size:2',
        ]);

        // Add the driver ID to the validated data for the API
        $validated['driverId'] = $id;

        try {
            $driver = $this->driverService->updateDriver($id, $validated);
            return redirect()->route('drivers.show', $id)->with('success', 'Driver updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update driver: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->driverService->deleteDriver($id);
            return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete driver: ' . $e->getMessage());
        }
    }

    /**
     * Get declarations for a specific driver by matching driver name
     */
    private function getDriverDeclarations(array $driver): array
    {
        try {
            // Get all declarations using pagination
            $allDeclarations = [];
            $startKey = null;

            do {
                $declarations = $this->declarationService->getDeclarationsPaginated(250, $startKey);
                $currentDeclarations = $declarations['items'] ?? $declarations ?? [];

                // Add current batch to all declarations
                $allDeclarations = array_merge($allDeclarations, $currentDeclarations);

                // Check if there are more pages
                $startKey = $declarations['lastEvaluatedKey'] ?? null;

            } while ($startKey);

            $declarationsData = $allDeclarations;

            $driverFullName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
            $driverDateOfBirth = $driver['driverDateOfBirth'] ?? null;

            $matchingDeclarations = [];

            foreach ($declarationsData as $declaration) {
                $declarationDriverName = $declaration['driverLatinFullName'] ?? null;
                $declarationDateOfBirth = $declaration['driverDateOfBirth'] ?? null;

                // Match by driver name and optionally by date of birth for better accuracy
                if ($declarationDriverName === $driverFullName) {
                    // If date of birth is available in both, use it for additional verification
                    if ($driverDateOfBirth && $declarationDateOfBirth) {
                        if ($driverDateOfBirth === $declarationDateOfBirth) {
                            $matchingDeclarations[] = $declaration;
                        }
                    } else {
                        // If no date of birth available, match by name only
                        $matchingDeclarations[] = $declaration;
                       
                    }
                }
            }

           

            // Sort declarations by creation date (newest first)
            usort($matchingDeclarations, function($a, $b) {
                $dateA = $a['declarationStartDate'] ?? '0000-00-00';
                $dateB = $b['declarationStartDate'] ?? '0000-00-00';
                return strcmp($dateB, $dateA);
            });

            return $matchingDeclarations;
        } catch (\Exception $e) {
          
            return [];
        }
    }

    /**
     * Get declarations for a specific driver (API endpoint for modal)
     */
    public function getDeclarations(string $driverId)
    {
        try {
            $driver = $this->driverService->getDriver($driverId);
            $declarations = $this->getDriverDeclarations($driver);

            return response()->json([
                'success' => true,
                'declarations' => $declarations,
                'count' => count($declarations)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load driver declarations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send selected declarations to driver email
     */
    public function sendDeclarations(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|string',
            'driver_email' => 'required|email',
            'declaration_ids' => 'required|array|min:1',
            'declaration_ids.*' => 'string',
            'language' => 'required|string|in:bg,cs,da,de,et,el,en,es,fr,fi,ga,hr,hu,it,lv,lt,mt,nl,no,pl,pt,ro,sk,sl,sv'
        ]);

        try {
            $driverId = $request->driver_id;
            $driverEmail = $request->driver_email;
            $declarationIds = $request->declaration_ids;
            $language = $request->language;

            $sentCount = 0;
            $errors = [];

            foreach ($declarationIds as $declarationId) {
                try {
                    $result = $this->declarationService->emailDeclaration($declarationId, $driverEmail, $language);

                    if ($result) {
                        $sentCount++;
                        \Log::info('Declaration email sent via bulk action', [
                            'declaration_id' => $declarationId,
                            'driver_id' => $driverId,
                            'driver_email' => $driverEmail,
                            'language' => $language,
                            'sent_at' => now()->toDateTimeString()
                        ]);
                    } else {
                        $errors[] = "Failed to send declaration {$declarationId}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error sending declaration {$declarationId}: " . $e->getMessage();
                    \Log::error('Bulk declaration email error', [
                        'declaration_id' => $declarationId,
                        'driver_id' => $driverId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $success = $sentCount > 0;
            $message = $sentCount > 0
                ? "Successfully sent {$sentCount} declaration(s) to {$driverEmail}"
                : 'No declarations were sent';

            if (!empty($errors)) {
                $message .= '. Errors: ' . implode(', ', $errors);
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'sent_count' => $sentCount,
                'error_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Bulk declaration email failed', [
                'driver_id' => $request->driver_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send declarations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk clone multiple drivers (with their SUBMITTED declarations) to target organizations
     */
    public function bulkClone(Request $request)
    {
        $request->validate([
            'driver_ids' => 'required|array|min:1',
            'driver_ids.*' => 'string',
            'target_user_ids' => 'required|array|min:1',
            'target_user_ids.*' => 'exists:users,id',
        ]);

        $driverIds = $request->driver_ids;
        $targetUserIds = $request->target_user_ids;

        // Validate all target users upfront
        $targetUsers = [];
        foreach ($targetUserIds as $targetUserId) {
            $targetUser = \App\Models\User::findOrFail($targetUserId);
            if (!auth()->user()->canImpersonateUser($targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => "You do not have access to organization: {$targetUser->name}"
                ], 403);
            }
            $targetUsers[$targetUserId] = $targetUser;
        }

        $apiService = app(\App\Services\PostingApiService::class);
        $sourceUser = auth()->user();
        $results = [];

        foreach ($driverIds as $driverId) {
            foreach ($targetUsers as $targetUser) {
                $resultKey = "{$driverId}__{$targetUser->id}";
                try {
                    // Switch to source credentials
                    $apiService->setUserCredentials(
                        $sourceUser->api_base_url,
                        $sourceUser->api_key,
                        $sourceUser->api_operator_id
                    );

                    // 1. Fetch source driver
                    $sourceDriver = $this->driverService->getDriver($driverId);
                    $driverName = trim(($sourceDriver['driverLatinFirstName'] ?? '') . ' ' . ($sourceDriver['driverLatinLastName'] ?? ''));

                    // 2. Prepare clone data
                    $cloneData = [
                        'driverLatinFirstName' => $sourceDriver['driverLatinFirstName'],
                        'driverLatinLastName' => $sourceDriver['driverLatinLastName'],
                        'driverDateOfBirth' => $sourceDriver['driverDateOfBirth'],
                        'driverLicenseNumber' => $sourceDriver['driverLicenseNumber'],
                        'driverDocumentType' => $sourceDriver['driverDocumentType'],
                        'driverDocumentNumber' => $sourceDriver['driverDocumentNumber'],
                        'driverDocumentIssuingCountry' => $sourceDriver['driverDocumentIssuingCountry'],
                        'driverAddressStreet' => $sourceDriver['driverAddressStreet'],
                        'driverAddressPostCode' => $sourceDriver['driverAddressPostCode'],
                        'driverAddressCity' => $sourceDriver['driverAddressCity'],
                        'driverAddressCountry' => $sourceDriver['driverAddressCountry'],
                        'driverContractStartDate' => $sourceDriver['driverContractStartDate'],
                        'driverApplicableLaw' => $sourceDriver['driverApplicableLaw'],
                    ];

                    // 3. Fetch source driver's declarations (still using source credentials)
                    $sourceDeclarations = $this->getDriverDeclarations($sourceDriver);
                    $submittedDeclarations = array_filter($sourceDeclarations, function ($d) {
                        return ($d['declarationStatus'] ?? '') === 'SUBMITTED';
                    });

                    // 4. Fetch full declaration details for each submitted declaration (source credentials)
                    $fullDeclarations = [];
                    foreach ($submittedDeclarations as $decl) {
                        try {
                            $fullDecl = $this->declarationService->getDeclaration($decl['declarationId']);
                            $fullDeclarations[] = $fullDecl;
                        } catch (\Exception $e) {
                            \Log::warning('Bulk clone: Failed to fetch declaration', [
                                'declaration_id' => $decl['declarationId'],
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // 5. Switch to target credentials
                    $apiService->setUserCredentials(
                        $targetUser->api_base_url,
                        $targetUser->api_key,
                        $targetUser->api_operator_id
                    );

                    // 6. Create driver in target org
                    $newDriver = $apiService->post(config('posting.endpoints.drivers'), $cloneData);

                    if (!isset($newDriver['driverId'])) {
                        throw new \Exception('No driver ID returned from target org.');
                    }

                    $newDriverId = $newDriver['driverId'];

                    // 7. Copy DriverProfile
                    $sourceProfile = \App\Models\DriverProfile::where('driver_id', $driverId)->first();
                    if ($sourceProfile) {
                        \App\Models\DriverProfile::create([
                            'driver_id' => $newDriverId,
                            'email' => $sourceProfile->email,
                            'auto_renew' => $sourceProfile->auto_renew,
                        ]);
                    }

                    // 8. Create and submit declarations in target org
                    $declResults = [];
                    foreach ($fullDeclarations as $fullDecl) {
                        try {
                            $declData = [
                                'driverId' => $newDriverId,
                                'declarationPostingCountry' => $fullDecl['declarationPostingCountry'] ?? null,
                                'declarationStartDate' => $fullDecl['declarationStartDate'] ?? null,
                                'declarationEndDate' => $fullDecl['declarationEndDate'] ?? null,
                                'declarationOperationType' => $fullDecl['declarationOperationType'] ?? [],
                                'declarationTransportType' => $fullDecl['declarationTransportType'] ?? [],
                                'declarationVehiclePlateNumber' => $fullDecl['declarationVehiclePlateNumber'] ?? [],
                            ];

                            // Copy otherContact fields if present
                            foreach (['otherContactAsTransportManager', 'otherContactFirstName', 'otherContactLastName', 'otherContactEmail', 'otherContactPhone'] as $field) {
                                if (isset($fullDecl[$field])) {
                                    $declData[$field] = $fullDecl[$field];
                                }
                            }

                            $newDecl = $this->declarationService->createDeclaration($declData);

                            if (isset($newDecl['declarationId'])) {
                                // Auto-submit
                                $this->declarationService->submitDeclaration($newDecl['declarationId']);

                                // Send email to driver if they have an email
                                $driverEmail = \App\Models\DriverProfile::getDriverEmail($newDriverId);
                                if ($driverEmail) {
                                    try {
                                        $this->declarationService->emailDeclaration($newDecl['declarationId'], $driverEmail, 'en');
                                    } catch (\Exception $emailEx) {
                                        \Log::warning('Bulk clone: Failed to send declaration email', [
                                            'declaration_id' => $newDecl['declarationId'],
                                            'driver_email' => $driverEmail,
                                            'error' => $emailEx->getMessage(),
                                        ]);
                                    }
                                }

                                $declResults[] = [
                                    'source_id' => $fullDecl['declarationId'] ?? 'unknown',
                                    'new_id' => $newDecl['declarationId'],
                                    'country' => $fullDecl['declarationPostingCountry'] ?? 'unknown',
                                    'success' => true,
                                ];
                            } else {
                                $declResults[] = [
                                    'source_id' => $fullDecl['declarationId'] ?? 'unknown',
                                    'success' => false,
                                    'error' => 'No declaration ID returned',
                                ];
                            }
                        } catch (\Exception $e) {
                            $declResults[] = [
                                'source_id' => $fullDecl['declarationId'] ?? 'unknown',
                                'success' => false,
                                'error' => $e->getMessage(),
                            ];
                        }
                    }

                    $results[$resultKey] = [
                        'success' => true,
                        'driver_id' => $driverId,
                        'driver_name' => $driverName,
                        'target_org' => $targetUser->name,
                        'new_driver_id' => $newDriverId,
                        'declarations_cloned' => count(array_filter($declResults, fn($r) => $r['success'])),
                        'declarations_failed' => count(array_filter($declResults, fn($r) => !$r['success'])),
                        'declaration_details' => $declResults,
                    ];

                    \Log::info('Bulk clone: Driver cloned successfully', [
                        'source_driver_id' => $driverId,
                        'new_driver_id' => $newDriverId,
                        'target_user_id' => $targetUser->id,
                        'declarations_cloned' => count(array_filter($declResults, fn($r) => $r['success'])),
                    ]);

                } catch (\Exception $e) {
                    $results[$resultKey] = [
                        'success' => false,
                        'driver_id' => $driverId,
                        'driver_name' => $driverName ?? $driverId,
                        'target_org' => $targetUser->name,
                        'error' => $e->getMessage(),
                    ];

                    \Log::error('Bulk clone: Failed', [
                        'driver_id' => $driverId,
                        'target_user_id' => $targetUser->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Switch back to source credentials
        $apiService->setUserCredentials(
            $sourceUser->api_base_url,
            $sourceUser->api_key,
            $sourceUser->api_operator_id
        );

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $failCount = count($results) - $successCount;

        return response()->json([
            'success' => $successCount > 0,
            'message' => "{$successCount} driver(s) cloned successfully, {$failCount} failed",
            'results' => array_values($results),
        ]);
    }

    /**
     * Clone a driver to another organization
     */
    public function clone(Request $request, string $sourceDriverId)
    {
        $request->validate([
            'target_user_id' => 'required|exists:users,id'
        ]);

        try {
            $targetUser = \App\Models\User::findOrFail($request->target_user_id);

            // Check permission - user must be able to impersonate target user
            if (!auth()->user()->canImpersonateUser($targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to the target organization'
                ], 403);
            }

            // 1. Fetch source driver from current user's organization
            $sourceDriver = $this->driverService->getDriver($sourceDriverId);

            // 2. Prepare data for cloning (only required fields)
            $cloneData = [
                'driverLatinFirstName' => $sourceDriver['driverLatinFirstName'],
                'driverLatinLastName' => $sourceDriver['driverLatinLastName'],
                'driverDateOfBirth' => $sourceDriver['driverDateOfBirth'],
                'driverLicenseNumber' => $sourceDriver['driverLicenseNumber'],
                'driverDocumentType' => $sourceDriver['driverDocumentType'],
                'driverDocumentNumber' => $sourceDriver['driverDocumentNumber'],
                'driverDocumentIssuingCountry' => $sourceDriver['driverDocumentIssuingCountry'],
                'driverAddressStreet' => $sourceDriver['driverAddressStreet'],
                'driverAddressPostCode' => $sourceDriver['driverAddressPostCode'],
                'driverAddressCity' => $sourceDriver['driverAddressCity'],
                'driverAddressCountry' => $sourceDriver['driverAddressCountry'],
                'driverContractStartDate' => $sourceDriver['driverContractStartDate'],
                'driverApplicableLaw' => $sourceDriver['driverApplicableLaw'],
            ];

            // 3. Get PostingApiService instance and switch to target user's credentials
            \Log::info('Clone: Switching to target user credentials', [
                'source_user_id' => auth()->id(),
                'source_operator_id' => auth()->user()->api_operator_id,
                'target_user_id' => $targetUser->id,
                'target_operator_id' => $targetUser->api_operator_id,
                'target_user_name' => $targetUser->name
            ]);

            $apiService = app(\App\Services\PostingApiService::class);
            $apiService->setUserCredentials(
                $targetUser->api_base_url,
                $targetUser->api_key,
                $targetUser->api_operator_id
            );

            // 4. Create driver in target organization
            \Log::info('Clone: Creating driver in target organization', [
                'target_user_id' => $targetUser->id,
                'target_operator_id' => $targetUser->api_operator_id,
                'driver_data' => $cloneData
            ]);

            $newDriver = $apiService->post(config('posting.endpoints.drivers'), $cloneData);

            if (!isset($newDriver['driverId'])) {
                throw new \Exception('Failed to create driver in target organization. No driver ID returned.');
            }

            $newDriverId = $newDriver['driverId'];

            // 5. Copy DriverProfile settings (email, auto_renew) if they exist
            $sourceProfile = \App\Models\DriverProfile::where('driver_id', $sourceDriverId)->first();
            if ($sourceProfile) {
                \App\Models\DriverProfile::create([
                    'driver_id' => $newDriverId,
                    'email' => $sourceProfile->email,
                    'auto_renew' => $sourceProfile->auto_renew,
                ]);
            }

            \Log::info('Driver cloned successfully', [
                'source_driver_id' => $sourceDriverId,
                'new_driver_id' => $newDriverId,
                'source_user_id' => auth()->id(),
                'target_user_id' => $targetUser->id,
                'target_organization' => $targetUser->name,
                'cloned_at' => now()->toDateTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Driver successfully cloned to {$targetUser->name}",
                'new_driver_id' => $newDriverId,
                'target_organization' => $targetUser->name
            ]);

        } catch (\Exception $e) {
            \Log::error('Driver clone failed', [
                'source_driver_id' => $sourceDriverId,
                'target_user_id' => $request->target_user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clone driver: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download driver authorization certificate
     */
    public function downloadCertificate(Request $request, string $id)
    {
        $request->validate([
            'hiring_company' => 'required|string'
        ]);

        try {
            $driver = $this->driverService->getDriver($id);
            $declarations = $this->getDriverDeclarations($driver);
            $hiringCompany = $request->hiring_company;

            // Calculate effective period from declarations
            $effectivePeriod = $this->calculateEffectivePeriod($declarations);

            // Prepare certificate data
            $leasedEmployee = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''))
                            . ' - NR. ' . ($driver['driverLicenseNumber'] ?? 'N/A');
            $leasingCompany = 'PWR WORK LTD - NR. HE467938';

            // Generate the PDF
            $pdf = $this->generateCertificatePDF($leasedEmployee, $leasingCompany, $hiringCompany, $effectivePeriod);

            // Output the PDF for download
            $fileName = 'Driver_Authorization_Certificate_' . ($driver['driverLatinLastName'] ?? 'Driver') . '_' . date('Y-m-d') . '.pdf';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate certificate: ' . $e->getMessage());
        }
    }

    /**
     * Calculate effective period from declarations
     * Gets the period from the last submitted declaration
     */
    private function calculateEffectivePeriod(array $declarations): string
    {
        if (empty($declarations)) {
            // Default to 6 months from today
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+6 months'));
            return $startDate . ' - ' . $endDate;
        }

        // Filter only SUBMITTED declarations
        $submittedDeclarations = array_filter($declarations, function($declaration) {
            return isset($declaration['declarationStatus']) && $declaration['declarationStatus'] === 'SUBMITTED';
        });

        if (empty($submittedDeclarations)) {
            // Default to 6 months from today if no submitted declarations
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+6 months'));
            return $startDate . ' - ' . $endDate;
        }

        // Sort by start date to get the most recent submitted declaration
        usort($submittedDeclarations, function($a, $b) {
            $dateA = $a['declarationStartDate'] ?? '0000-00-00';
            $dateB = $b['declarationStartDate'] ?? '0000-00-00';
            return strcmp($dateB, $dateA); // Descending order (newest first)
        });

        // Get the last (most recent) submitted declaration
        $lastSubmittedDeclaration = $submittedDeclarations[0];

        $startDate = $lastSubmittedDeclaration['declarationStartDate'] ?? date('Y-m-d');
        $endDate = $lastSubmittedDeclaration['declarationEndDate'] ?? date('Y-m-d', strtotime('+6 months'));

        return $startDate . ' - ' . $endDate;
    }

    /**
     * Generate certificate PDF with filled information
     */
    private function generateCertificatePDF(string $leasedEmployee, string $leasingCompany, string $hiringCompany, string $effectivePeriod): string
    {
        try {
            // Path to the template PDF
            $templatePath = public_path('DRIVER AUTHORIZATION CERTIFICATE.pdf');

            if (!file_exists($templatePath)) {
                throw new \Exception('PDF template not found at: ' . $templatePath);
            }

            // Initialize FPDI with FPDF
            $pdf = new \setasign\Fpdi\Fpdi();

            // Set the PDF parser to handle PDF 1.7
            $pdf->setSourceFile($templatePath);

            // Import the first page
            $templateId = $pdf->importPage(1);

            // Get the size of the imported page
            $size = $pdf->getTemplateSize($templateId);

            // Add a page with the same orientation and size as the template
            if ($size['width'] > $size['height']) {
                $pdf->AddPage('L', [$size['width'], $size['height']]);
            } else {
                $pdf->AddPage('P', [$size['width'], $size['height']]);
            }

            // Use the imported page as template
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            // Set font for adding text
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);

            // Add Leased Employee (positioned after "Leased Employee:")
            $pdf->SetXY(20, 50); // Adjust X,Y coordinates as needed
            $pdf->Write(0, $leasedEmployee);

            // Add Leasing Company (positioned after "Leasing Company:")
            $pdf->SetXY(20, 70); // Adjust Y coordinate
            $pdf->Write(0, $leasingCompany);

            // Add Hiring Company (positioned after "Hiring Company (Lessee):")
            $pdf->SetXY(20, 90); // Adjust Y coordinate
            $pdf->Write(0, $hiringCompany);

            // Add Effective Period (positioned after "Effective Period:")
            $pdf->SetXY(20, 144); // Adjust Y coordinate
            $pdf->Write(0, $effectivePeriod);

            // Return PDF as string
            return $pdf->Output('S');

        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
