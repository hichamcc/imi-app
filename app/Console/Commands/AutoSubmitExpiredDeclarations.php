<?php

namespace App\Console\Commands;

use App\Services\PostingApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoSubmitExpiredDeclarations extends Command
{
    protected $signature = 'declarations:auto-submit';
    protected $description = 'Automatically create and submit new declarations for drivers with expired declarations (end date was yesterday)';

    protected PostingApiService $apiService;

    public function __construct(PostingApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
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

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $expiredCount = 0;
        $createdCount = 0;
        $submittedCount = 0;
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
        $this->line("  - New declarations created: {$createdCount}");
        $this->line("  - New declarations submitted: {$submittedCount}");
        $this->line("  - Errors: {$errorCount}");
        $this->line("  - Execution time: {$executionTime} seconds");

        Log::info('AUTO-SUBMIT: Process completed', [
            'completed_at' => now()->toDateTimeString(),
            'expired_count' => $expiredCount,
            'created_count' => $createdCount,
            'submitted_count' => $submittedCount,
            'error_count' => $errorCount,
            'execution_time_seconds' => $executionTime,
            'peak_memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'success_rate' => $expiredCount > 0 ? round(($submittedCount / $expiredCount) * 100, 2) . '%' : '100%'
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
        // Calculate new end date (start today, end in 30 days - or same duration as original)
        $originalStart = Carbon::parse($originalDeclaration['declarationStartDate']);
        $originalEnd = Carbon::parse($originalDeclaration['declarationEndDate']);
        $originalDuration = $originalStart->diffInDays($originalEnd);

        $newEndDate = Carbon::parse($newStartDate)->addDays($originalDuration)->format('Y-m-d');

        // Prepare new declaration data - copy relevant fields only
        $newData = [
            'declarationPostingCountry' => $originalDeclaration['declarationPostingCountry'],
            'declarationStartDate' => $newStartDate,
            'declarationEndDate' => $newEndDate,
            'declarationOperationType' => $originalDeclaration['declarationOperationType'] ?? ['INTERNATIONAL_CARRIAGE'],
            'declarationTransportType' => $originalDeclaration['declarationTransportType'] ?? ['CARRIAGE_OF_GOODS'],
            'declarationVehiclePlateNumber' => $originalDeclaration['declarationVehiclePlateNumber'] ?? [],
            'driverId' => $originalDeclaration['driverId'],
        ];

        // Add optional fields if they exist
        $optionalFields = [
            'otherContactAsTransportManager',
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
}