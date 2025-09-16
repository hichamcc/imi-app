<?php

namespace App\Console\Commands;

use App\Services\PostingApiService;
use Illuminate\Console\Command;

class TestApiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posting:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to the EU Road Transport Posting Declaration API';

    /**
     * Execute the console command.
     */
    public function handle(PostingApiService $apiService)
    {
        $this->info('Testing EU Road Transport Posting Declaration API connection...');

        // Check if API is configured
        if (!$apiService->isConfigured()) {
            $this->error('API is not properly configured. Please check your environment variables:');
            $this->line('- POSTING_API_BASE_URL');
            $this->line('- POSTING_API_KEY');
            return 1;
        }

        $this->info('Configuration check: ✓ Passed');

        // Test connection
        $this->info('Testing API connectivity...');

        if ($apiService->testConnection()) {
            $this->info('API connection test: ✓ Passed');
            $this->info('🎉 Successfully connected to the Posting Declaration API!');
            return 0;
        } else {
            $this->error('API connection test: ✗ Failed');
            $this->error('Unable to connect to the API. Please check:');
            $this->line('- Your internet connection');
            $this->line('- API key validity');
            $this->line('- API endpoint availability');
            return 1;
        }
    }
}
