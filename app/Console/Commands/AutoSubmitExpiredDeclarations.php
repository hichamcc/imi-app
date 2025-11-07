<?php

namespace App\Console\Commands;

use App\Services\PostingApiService;
use App\Services\DeclarationService;
use App\Models\DriverProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoSubmitExpiredDeclarations extends Command
{
    protected $signature = 'declarations:auto-submit';
    protected $description = 'Automatically create and submit new declarations for drivers with expired declarations (end date was yesterday)';

    protected PostingApiService $apiService;
    protected DeclarationService $declarationService;

    public function __construct(PostingApiService $apiService, DeclarationService $declarationService)
    {
        parent::__construct();
        $this->apiService = $apiService;
        $this->declarationService = $declarationService;
    }

    public function handle()
    {
        $startTime = microtime(true);
        $this->info('Starting auto-submit process for expired declarations...');

        // Log the start of the process
        Log::info('AUTO-SUBMIT: Process started', [
            'started_at' => now()->toDateTimeString(),
            'yesterday' => Carbon::yesterday()->format('Y-m-d'),
            'today' => Carbon::today()->format('Y-m-d'),
            'process_id' => getmypid(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ]);

        $yesterday = Carbon::parse('2025-10-05')->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $expiredCount = 0;
        $createdCount = 0;
        $submittedCount = 0;
        $emailsSentCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        try {
            // Get all users who have valid API credentials
            $users = \App\Models\User::whereNotNull('api_key')
                ->whereNotNull('api_operator_id')
                ->whereNotNull('api_base_url')
                ->where('is_active', true)
                ->get();

            $this->info("Found {$users->count()} users with valid API credentials");

        

            foreach ($users as $user) {
                $this->line("Processing user: {$user->name} (ID: {$user->id})");

               

                try {
                    // Set user credentials for API service
                    $this->apiService->setUserCredentials(
                        $user->api_base_url,
                        $user->api_key,
                        $user->api_operator_id
                    );

                    // Get ALL declarations for this user using pagination
                    $allDeclarations = [];
                    $startKey = null;

                    do {
                        $params = ['limit' => 250];
                        if ($startKey) {
                            $params['startKey'] = $startKey;
                        }

                        $declarationsBatch = $this->apiService->get('/declarations', $params);
                        $currentDeclarations = $declarationsBatch['items'] ?? [];

                        // Add current batch to all declarations
                        $allDeclarations = array_merge($allDeclarations, $currentDeclarations);

                        // Check if there are more pages
                        $startKey = $declarationsBatch['lastEvaluatedKey'] ?? null;

                      

                    } while ($startKey);

                    // Create the declarations array in the expected format
                    $declarations = [
                        'count' => count($allDeclarations),
                        'items' => $allDeclarations,
                        'lastEvaluatedKey' => null
                    ];

                   

                    if (isset($declarations['items']) && is_array($declarations['items'])) {
                        // Log some sample declarations for debugging
                        $sampleDeclarations = array_slice($declarations['items'], 0, 3);
                      

                        foreach ($declarations['items'] as $declaration) {
                            // Check if declaration expired yesterday and needs renewal
                            if ($this->shouldCreateNewDeclaration($declaration, $yesterday)) {
                                $expiredCount++;

                                $this->line("  - Found expired declaration: {$declaration['declarationId']} (End date: {$declaration['declarationEndDate']})");

                                try {
                                    // Fetch full declaration details to get driverId and other required fields
                                    $fullDeclaration = $this->apiService->get("/declarations/{$declaration['declarationId']}");

                                    if (!isset($fullDeclaration['driverId'])) {
                                        throw new \Exception('Full declaration data does not contain driverId');
                                    }

                                    // Check if auto-renew is enabled for this driver
                                    $driverId = $fullDeclaration['driverId'];
                                    $autoRenewEnabled = DriverProfile::isAutoRenewEnabled($driverId);

                                    if (!$autoRenewEnabled) {
                                        $this->line("    ⏭ Skipping auto-renewal for driver {$driverId} (auto-renew disabled)");
                                        $skippedCount++;
                                        continue;
                                    }

                                    // Create new declaration with updated dates using full declaration data
                                    $newDeclarationData = $this->prepareNewDeclarationData($fullDeclaration, $today);

                                    $this->line("    Creating new declaration with start date: {$newDeclarationData['declarationStartDate']} and end date: {$newDeclarationData['declarationEndDate']}");

                                    $createResult = $this->apiService->post('/declarations', $newDeclarationData);

                                    if (isset($createResult['declarationId'])) {
                                        $newDeclarationId = $createResult['declarationId'];
                                        $createdCount++;
                                        $this->info("    ✓ Successfully created new declaration {$newDeclarationId}");

                                        // Now submit the newly created declaration
                                        $submitResult = $this->apiService->post("/declarations/{$newDeclarationId}/submit");

                                        if (isset($submitResult['success']) || isset($submitResult['status']) || $submitResult !== null) {
                                            $submittedCount++;
                                            $this->info("    ✓ Successfully submitted new declaration {$newDeclarationId}");

                                            // Check if driver has email and send declaration
                                            $this->sendDeclarationEmailIfDriverHasEmail($newDeclarationId, $fullDeclaration, $emailsSentCount);
                                        } else {
                                            $this->error("    ✗ Failed to submit new declaration {$newDeclarationId}");
                                            $errorCount++;
                                        }

                                    } else {
                                        throw new \Exception('Failed to create declaration. API response: ' . json_encode($createResult));
                                    }

                                } catch (\Exception $e) {
                                    $errorCount++;
                                    $this->error("    ✗ Failed to create/submit new declaration for {$declaration['declarationId']}: " . $e->getMessage());

                                 
                                }
                            }
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("Failed to process user {$user->name}: " . $e->getMessage());
                   
                }
            }

        } catch (\Exception $e) {
            $this->error("Auto-submit process failed: " . $e->getMessage());
            Log::error('Auto-submit process failed', ['error' => $e->getMessage()]);
            return 1;
        }

        // Summary
        $executionTime = round((microtime(true) - $startTime), 2);
        $this->newLine();
        $this->info('Auto-submit process completed:');
        $this->line("  - Expired declarations found: {$expiredCount}");
        $this->line("  - Skipped (auto-renew disabled): {$skippedCount}");
        $this->line("  - New declarations created: {$createdCount}");
        $this->line("  - New declarations submitted: {$submittedCount}");
        $this->line("  - Emails sent to drivers: {$emailsSentCount}");
        $this->line("  - Errors: {$errorCount}");
        $this->line("  - Execution time: {$executionTime} seconds");

        Log::info('AUTO-SUBMIT: Process completed', [
            'completed_at' => now()->toDateTimeString(),
            'expired_count' => $expiredCount,
            'skipped_count' => $skippedCount,
            'created_count' => $createdCount,
            'submitted_count' => $submittedCount,
            'emails_sent_count' => $emailsSentCount,
            'error_count' => $errorCount,
            'execution_time_seconds' => $executionTime,
            'peak_memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'processed_count' => $expiredCount - $skippedCount,
            'success_rate' => ($expiredCount - $skippedCount) > 0 ? round(($submittedCount / ($expiredCount - $skippedCount)) * 100, 2) . '%' : '100%'
        ]);

        return 0;
    }

    /**
     * Determine if a declaration should trigger creation of a new one
     */
    private function shouldCreateNewDeclaration(array $declaration, string $yesterday): bool
    {
        $declarationId = $declaration['declarationId'] ?? 'unknown';

        // Check if declaration has an end date
        if (!isset($declaration['declarationEndDate']) || empty($declaration['declarationEndDate'])) {
       
            return false;
        }

        // Check if end date was exactly yesterday
        $endDate = Carbon::parse($declaration['declarationEndDate'])->format('Y-m-d');
        if ($endDate !== $yesterday) {
        
            return false;
        }

        // Only process expired declarations
        $status = $declaration['declarationStatus'] ?? '';
        if (strtoupper($status) !== 'EXPIRED') {
       
            return false;
        }

       

        return true;
    }

    /**
     * Prepare data for new declaration based on expired one
     */
    private function prepareNewDeclarationData(array $originalDeclaration, string $newStartDate): array
    {
        // Calculate new end date (start today, end in same duration as original, but max 6 months)
        $originalStart = Carbon::parse($originalDeclaration['declarationStartDate']);
        $originalEnd = Carbon::parse($originalDeclaration['declarationEndDate']);
        $originalDuration = $originalStart->diffInDays($originalEnd);

        // Cap duration to maximum 6 months (180 days) to comply with API limits
        $maxDuration = 180;
        $safeDuration = min($originalDuration, $maxDuration);

        $newEndDate = Carbon::parse($newStartDate)->addDays($safeDuration)->format('Y-m-d');

        // Handle contact information properly based on existing declaration
        $otherContactAsTransportManager = $originalDeclaration['otherContactAsTransportManager'] ?? false;

        // If contact fields are incomplete but otherContactAsTransportManager is false,
        // set it to true to avoid validation errors during auto-submit
        if (!$otherContactAsTransportManager) {
            $firstName = $originalDeclaration['otherContactFirstName'] ?? '';
            $lastName = $originalDeclaration['otherContactLastName'] ?? '';
            if (empty(trim($firstName)) || empty(trim($lastName))) {
                $otherContactAsTransportManager = true;
            }
        }

        // Prepare new declaration data - copy relevant fields only
        $newData = [
            'declarationPostingCountry' => $originalDeclaration['declarationPostingCountry'],
            'declarationStartDate' => $newStartDate,
            'declarationEndDate' => $newEndDate,
            'declarationOperationType' => $originalDeclaration['declarationOperationType'] ?? ['INTERNATIONAL_CARRIAGE'],
            'declarationTransportType' => $originalDeclaration['declarationTransportType'] ?? ['CARRIAGE_OF_GOODS'],
            'declarationVehiclePlateNumber' => $originalDeclaration['declarationVehiclePlateNumber'] ?? [],
            'driverId' => $originalDeclaration['driverId'],
            'otherContactAsTransportManager' => $otherContactAsTransportManager,
        ];

        // Add optional contact fields if they exist
        $optionalFields = [
            'otherContactFirstName',
            'otherContactLastName',
            'otherContactEmail',
            'otherContactPhone'
        ];

        foreach ($optionalFields as $field) {
            if (isset($originalDeclaration[$field])) {
                $newData[$field] = $originalDeclaration[$field];
            }
        }

        return $newData;
    }

    /**
     * Check if driver has email and send declaration in English
     */
    private function sendDeclarationEmailIfDriverHasEmail(string $declarationId, array $declaration, int &$emailsSentCount): void
    {
        try {
            $driverId = $declaration['driverId'] ?? null;

            if (!$driverId) {
                $this->line("    ℹ No driver ID found for declaration {$declarationId}");
                return;
            }

            // Get driver email from driver profiles
            $driverEmail = DriverProfile::getDriverEmail($driverId);

            if (!$driverEmail) {
                $this->line("    ℹ No email found for driver {$driverId}");
                return;
            }

            $this->line("    📧 Sending declaration email to driver {$driverId} at {$driverEmail}");

            // Set declaration service to use current API credentials
            $this->declarationService->setApiService($this->apiService);

            // Send email in English
            $emailResult = $this->declarationService->emailDeclaration($declarationId, $driverEmail, 'en');

            if ($emailResult) {
                $emailsSentCount++;
                $this->info("    ✓ Successfully sent declaration email to {$driverEmail}");

                Log::info('AUTO-SUBMIT: Declaration email sent', [
                    'declaration_id' => $declarationId,
                    'driver_id' => $driverId,
                    'driver_email' => $driverEmail,
                    'language' => 'en',
                    'sent_at' => now()->toDateTimeString()
                ]);
            } else {
                $this->error("    ✗ Failed to send declaration email to {$driverEmail}");

                Log::warning('AUTO-SUBMIT: Failed to send declaration email', [
                    'declaration_id' => $declarationId,
                    'driver_id' => $driverId,
                    'driver_email' => $driverEmail,
                    'error' => 'Email API returned false/null'
                ]);
            }

        } catch (\Exception $e) {
            $this->error("    ✗ Failed to send declaration email: " . $e->getMessage());

            Log::error('AUTO-SUBMIT: Email sending error', [
                'declaration_id' => $declarationId,
                'driver_id' => $declaration['driverId'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}