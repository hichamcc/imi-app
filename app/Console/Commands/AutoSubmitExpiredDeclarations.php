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
        $this->info('Starting auto-submit process for expired declarations...');

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

                    // Get all declarations for this user (API max limit is 250)
                    $declarations = $this->apiService->get('/declarations', ['limit' => 250]);

                    if (isset($declarations['items']) && is_array($declarations['items'])) {
                        foreach ($declarations['items'] as $declaration) {
                            // Check if declaration expired yesterday and needs renewal
                            if ($this->shouldCreateNewDeclaration($declaration, $yesterday)) {
                                $expiredCount++;

                                $this->line("  - Found expired declaration: {$declaration['declarationId']} (End date: {$declaration['declarationEndDate']})");

                                try {
                                    // Create new declaration with updated dates
                                    $newDeclarationData = $this->prepareNewDeclarationData($declaration, $today);

                                    $this->line("    Creating new declaration with start date: {$newDeclarationData['start_date']} and end date: {$newDeclarationData['end_date']}");

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

                                            Log::info('Auto-created and submitted new declaration for expired one', [
                                                'user_id' => $user->id,
                                                'original_declaration_id' => $declaration['declarationId'],
                                                'new_declaration_id' => $newDeclarationId,
                                                'original_end_date' => $declaration['declarationEndDate'],
                                                'new_start_date' => $newDeclarationData['declarationStartDate'],
                                                'new_end_date' => $newDeclarationData['declarationEndDate'],
                                                'created_and_submitted_at' => now()
                                            ]);
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

                                    Log::error('Failed to auto-create/submit new declaration', [
                                        'user_id' => $user->id,
                                        'original_declaration_id' => $declaration['declarationId'],
                                        'original_end_date' => $declaration['declarationEndDate'],
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("Failed to process user {$user->name}: " . $e->getMessage());
                    Log::error('Failed to process user in auto-submit', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            $this->error("Auto-submit process failed: " . $e->getMessage());
            Log::error('Auto-submit process failed', ['error' => $e->getMessage()]);
            return 1;
        }

        // Summary
        $this->newLine();
        $this->info('Auto-submit process completed:');
        $this->line("  - Expired declarations found: {$expiredCount}");
        $this->line("  - New declarations created: {$createdCount}");
        $this->line("  - New declarations submitted: {$submittedCount}");
        $this->line("  - Errors: {$errorCount}");

        Log::info('Auto-submit process completed', [
            'expired_count' => $expiredCount,
            'created_count' => $createdCount,
            'submitted_count' => $submittedCount,
            'error_count' => $errorCount
        ]);

        return 0;
    }

    /**
     * Determine if a declaration should trigger creation of a new one
     */
    private function shouldCreateNewDeclaration(array $declaration, string $yesterday): bool
    {
        // Check if declaration has an end date
        if (!isset($declaration['declarationEndDate']) || empty($declaration['declarationEndDate'])) {
            return false;
        }

        // Check if end date was yesterday
        $endDate = Carbon::parse($declaration['declarationEndDate'])->format('Y-m-d');
        if ($endDate !== $yesterday) {
            return false;
        }

        // Only process submitted declarations (not drafts)
        $status = $declaration['declarationStatus'] ?? '';
        if (!in_array(strtoupper($status), ['SUBMITTED'])) {
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