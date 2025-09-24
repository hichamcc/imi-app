<?php

namespace App\Console\Commands;

use App\Services\PostingApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestDeclarationFields extends Command
{
    protected $signature = 'test:declaration-fields';
    protected $description = 'Test and display the actual structure of declaration data from the API';

    protected PostingApiService $apiService;

    public function __construct(PostingApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        $this->info('Testing declaration API response structure...');

        try {
            // Get all users who have valid API credentials
            $users = \App\Models\User::whereNotNull('api_key')
                ->whereNotNull('api_operator_id')
                ->whereNotNull('api_base_url')
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                $this->error('No users found with valid API credentials');
                return 1;
            }

            $this->info("Found {$users->count()} users with valid API credentials");

            foreach ($users as $user) {
                $this->line("Testing with user: {$user->name} (ID: {$user->id})");

                try {
                    // Set user credentials for API service
                    $this->apiService->setUserCredentials(
                        $user->api_base_url,
                        $user->api_key,
                        $user->api_operator_id
                    );

                    // Get declarations for this user
                    $declarations = $this->apiService->get('/declarations', ['limit' => 5]);

                    if (isset($declarations['data']) && is_array($declarations['data'])) {
                        $this->info("Found {$declarations['meta']['total']} total declarations for user {$user->name}");

                        if (count($declarations['data']) > 0) {
                            $firstDeclaration = $declarations['data'][0];

                            $this->newLine();
                            $this->line('=== FIRST DECLARATION STRUCTURE ===');
                            $this->line('Available fields in declaration:');

                            foreach ($firstDeclaration as $key => $value) {
                                $type = gettype($value);
                                $displayValue = $type === 'string' ? substr($value, 0, 50) : $value;
                                if (is_array($displayValue)) {
                                    $displayValue = 'Array(' . count($displayValue) . ')';
                                } elseif (is_object($displayValue)) {
                                    $displayValue = 'Object';
                                } elseif (is_bool($displayValue)) {
                                    $displayValue = $displayValue ? 'true' : 'false';
                                }

                                $this->line("  {$key} ({$type}): {$displayValue}");
                            }

                            $this->newLine();
                            $this->line('=== FULL DECLARATION SAMPLE ===');
                            $this->line(json_encode($firstDeclaration, JSON_PRETTY_PRINT));

                            // Log the structure for reference
                            Log::info('Declaration API structure test', [
                                'user_id' => $user->id,
                                'fields' => array_keys($firstDeclaration),
                                'sample_declaration' => $firstDeclaration
                            ]);

                            return 0; // Exit after first successful test
                        } else {
                            $this->warn("No declarations found for user {$user->name}");
                        }
                    } else {
                        $this->error("Unexpected API response structure for user {$user->name}");
                        $this->line("Response: " . json_encode($declarations));
                    }

                } catch (\Exception $e) {
                    $this->error("Failed to test with user {$user->name}: " . $e->getMessage());
                    continue;
                }
            }

        } catch (\Exception $e) {
            $this->error("Test failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}