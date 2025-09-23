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

                    // Get all declarations for this user
                    $declarations = $this->apiService->get('/declarations', ['limit' => 1000]);

                    if (isset($declarations['data']) && is_array($declarations['data'])) {
                        foreach ($declarations['data'] as $declaration) {
                            // Check if declaration expired yesterday and needs renewal
                            if ($this->shouldCreateNewDeclaration($declaration, $yesterday)) {
                                $expiredCount++;

                                $this->line("  - Found expired declaration: {$declaration['id']} (End date: {$declaration['end_date']})");

                                try {
                                    // Create new declaration with updated dates
                                    $newDeclarationData = $this->prepareNewDeclarationData($declaration, $today);

                                    $this->line("    Creating new declaration with start date: {$newDeclarationData['start_date']} and end date: {$newDeclarationData['end_date']}");

                                    $createResult = $this->apiService->post('/declarations', $newDeclarationData);

                                    if (isset($createResult['id']) || isset($createResult['data']['id'])) {
                                        $newDeclarationId = $createResult['id'] ?? $createResult['data']['id'];
                                        $createdCount++;
                                        $this->info("    ✓ Successfully created new declaration {$newDeclarationId}");

                                        // Now submit the newly created declaration
                                        $submitResult = $this->apiService->post("/declarations/{$newDeclarationId}/submit");

                                        if (isset($submitResult['success']) || isset($submitResult['status']) || $submitResult !== null) {
                                            $submittedCount++;
                                            $this->info("    ✓ Successfully submitted new declaration {$newDeclarationId}");

                                            Log::info('Auto-created and submitted new declaration for expired one', [
                                                'user_id' => $user->id,
                                                'original_declaration_id' => $declaration['id'],
                                                'new_declaration_id' => $newDeclarationId,
                                                'original_end_date' => $declaration['end_date'],
                                                'new_start_date' => $newDeclarationData['start_date'],
                                                'new_end_date' => $newDeclarationData['end_date'],
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
                                    $this->error("    ✗ Failed to create/submit new declaration for {$declaration['id']}: " . $e->getMessage());

                                    Log::error('Failed to auto-create/submit new declaration', [
                                        'user_id' => $user->id,
                                        'original_declaration_id' => $declaration['id'],
                                        'original_end_date' => $declaration['end_date'],
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
        if (!isset($declaration['end_date']) || empty($declaration['end_date'])) {
            return false;
        }

        // Check if end date was yesterday
        $endDate = Carbon::parse($declaration['end_date'])->format('Y-m-d');
        if ($endDate !== $yesterday) {
            return false;
        }

        // Only process submitted declarations (not drafts)
        $status = $declaration['status'] ?? '';
        if (!in_array(strtolower($status), ['submitted', 'approved'])) {
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
        $originalStart = Carbon::parse($originalDeclaration['start_date']);
        $originalEnd = Carbon::parse($originalDeclaration['end_date']);
        $originalDuration = $originalStart->diffInDays($originalEnd);

        $newEndDate = Carbon::parse($newStartDate)->addDays($originalDuration)->format('Y-m-d');

        // Prepare new declaration data - copy everything except dates and ID
        $newData = $originalDeclaration;

        // Remove fields that shouldn't be copied
        unset($newData['id']);
        unset($newData['status']);
        unset($newData['created_at']);
        unset($newData['updated_at']);
        unset($newData['submitted_at']);

        // Update dates
        $newData['start_date'] = $newStartDate;
        $newData['end_date'] = $newEndDate;

        return $newData;
    }
}