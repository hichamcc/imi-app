<?php

namespace App\Console\Commands;

use App\Services\DeclarationService;
use Illuminate\Console\Command;

class TestDeclarations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'declarations:test {--limit=10 : Number of declarations to fetch} {--startKey= : Start key for pagination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the declarations API integration by fetching and displaying declarations';

    protected DeclarationService $declarationService;

    public function __construct(DeclarationService $declarationService)
    {
        parent::__construct();
        $this->declarationService = $declarationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Declarations API Integration...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $startKey = $this->option('startKey');

        try {
            // Test basic connection and list declarations
            $this->info("Fetching declarations (limit: {$limit}" . ($startKey ? ", startKey: {$startKey}" : '') . ')...');

            $response = $this->declarationService->getDeclarationsPaginated($limit, $startKey);

            if (isset($response['items']) && is_array($response['items'])) {
                $declarations = $response['items'];
                $this->info("✅ Successfully fetched " . count($declarations) . " declaration(s)");
                $this->newLine();

                // Display declarations in a table
                if (count($declarations) > 0) {
                    $tableData = [];
                    foreach ($declarations as $declaration) {
                        $tableData[] = [
                            substr($declaration['declarationId'] ?? 'N/A', 0, 8) . '...',
                            $declaration['driverLatinFullName'] ?? 'N/A',
                            strtoupper($declaration['declarationPostingCountry'] ?? 'N/A'),
                            $declaration['declarationStartDate'] ?? 'N/A',
                            $declaration['declarationEndDate'] ?? 'N/A',
                            ucfirst(strtolower($declaration['declarationStatus'] ?? 'N/A'))
                        ];
                    }

                    $this->table(
                        ['Declaration ID', 'Driver', 'Country', 'Start Date', 'End Date', 'Status'],
                        $tableData
                    );

                    // Show pagination info
                    if (isset($response['lastEvaluatedKey'])) {
                        $this->info("📄 Next page available with startKey: " . $response['lastEvaluatedKey']);
                        $this->info("Run: php artisan declarations:test --startKey=\"{$response['lastEvaluatedKey']}\"");
                    } else {
                        $this->info("📄 No more pages available");
                    }
                } else {
                    $this->warn("No declarations found");
                }
            } else {
                $this->error("❌ Unexpected response format");
                $this->line("Response: " . json_encode($response, JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to fetch declarations: " . $e->getMessage());
            $this->newLine();

            if ($this->option('verbose')) {
                $this->line("Exception details:");
                $this->line("Class: " . get_class($e));
                $this->line("File: " . $e->getFile() . ":" . $e->getLine());
                $this->line("Trace: " . $e->getTraceAsString());
            }
        }

        $this->newLine();
        $this->info('Test completed!');
    }
}