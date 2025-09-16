<?php

namespace App\Console\Commands;

use App\Services\DriverService;
use Illuminate\Console\Command;

class TestDrivers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:test {--limit=10 : Number of drivers to fetch} {--startKey= : Start key for pagination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the drivers API integration by fetching and displaying drivers';

    protected DriverService $driverService;

    public function __construct(DriverService $driverService)
    {
        parent::__construct();
        $this->driverService = $driverService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Drivers API Integration...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $startKey = $this->option('startKey');

        try {
            // Test basic connection and list drivers
            $this->info("Fetching drivers (limit: {$limit}" . ($startKey ? ", startKey: {$startKey}" : '') . ')...');

            $response = $this->driverService->getDriversPaginated($limit, $startKey);

            if (isset($response['items']) && is_array($response['items'])) {
                $drivers = $response['items'];
                $this->info("✅ Successfully fetched " . count($drivers) . " driver(s)");
                $this->newLine();

                // Display drivers in a table
                if (count($drivers) > 0) {
                    $tableData = [];
                    foreach ($drivers as $driver) {
                        $tableData[] = [
                            $driver['driverId'] ?? 'N/A',
                            trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')),
                            $driver['driverDateOfBirth'] ?? 'N/A',
                            $driver['driverHasDeclarations'] ? 'Yes' : 'No',
                            $driver['driverCountActiveDeclarations'] ?? 0
                        ];
                    }

                    $this->table(
                        ['Driver ID', 'Name', 'Date of Birth', 'Has Declarations', 'Active Declarations'],
                        $tableData
                    );

                    // Show pagination info
                    if (isset($response['lastEvaluatedKey'])) {
                        $this->info("📄 Next page available with startKey: " . $response['lastEvaluatedKey']);
                        $this->info("Run: php artisan drivers:test --startKey=\"{$response['lastEvaluatedKey']}\"");
                    } else {
                        $this->info("📄 No more pages available");
                    }
                } else {
                    $this->warn("No drivers found");
                }
            } else {
                $this->error("❌ Unexpected response format");
                $this->line("Response: " . json_encode($response, JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to fetch drivers: " . $e->getMessage());
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
