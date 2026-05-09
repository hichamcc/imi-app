<?php

namespace App\Console\Commands;

use App\Models\DriverProfile;
use App\Models\User;
use App\Services\DriverService;
use App\Services\PostingApiService;
use Illuminate\Console\Command;

class BackfillDriverAddressCountry extends Command
{
    protected $signature = 'drivers:backfill-address-country
                            {--user= : Only backfill for a specific user ID}
                            {--dry-run : Show what would be updated without saving}';

    protected $description = 'Backfill address_country in driver_profiles from the API for existing drivers';

    public function handle(PostingApiService $apiService, DriverService $driverService): int
    {
        $dryRun = $this->option('dry-run');

        $users = $this->option('user')
            ? User::where('id', $this->option('user'))->get()
            : User::whereNotNull('api_key')->whereNotNull('api_base_url')->whereNotNull('api_operator_id')->get();

        if ($users->isEmpty()) {
            $this->error('No users with API credentials found.');
            return self::FAILURE;
        }

        foreach ($users as $user) {
            $this->info("Processing user: {$user->name} (ID: {$user->id})");

            $apiService->setUserCredentials(
                $user->api_base_url,
                $user->api_key,
                $user->api_operator_id
            );

            $startKey = null;
            $totalUpdated = 0;
            $totalSkipped = 0;

            do {
                $params = ['limit' => 250];
                if ($startKey) {
                    $params['startKey'] = $startKey;
                }

                try {
                    $result = $driverService->getDrivers($params);
                } catch (\Exception $e) {
                    $this->error("  Failed to fetch drivers: {$e->getMessage()}");
                    break;
                }

                $drivers = $result['items'] ?? $result ?? [];
                $startKey = $result['lastEvaluatedKey'] ?? null;

                foreach ($drivers as $driver) {
                    $driverId = $driver['driverId'] ?? null;
                    if (!$driverId) {
                        continue;
                    }

                    // Skip if already set
                    $profile = DriverProfile::where('driver_id', $driverId)->first();
                    if ($profile && !empty($profile->address_country)) {
                        $totalSkipped++;
                        continue;
                    }

                    // Fetch full driver to get address country
                    try {
                        $fullDriver = $driverService->getDriver($driverId);
                        $addressCountry = strtoupper($fullDriver['driverAddressCountry'] ?? '');

                        if (empty($addressCountry)) {
                            $this->warn("  Driver {$driverId}: no address country in API, skipping.");
                            $totalSkipped++;
                            continue;
                        }

                        $driverName = trim(($fullDriver['driverLatinFirstName'] ?? '') . ' ' . ($fullDriver['driverLatinLastName'] ?? ''));
                        $this->line("  [{$driverName}] → {$addressCountry}" . ($dryRun ? ' (dry run)' : ''));

                        if (!$dryRun) {
                            DriverProfile::updateOrCreate(
                                ['driver_id' => $driverId],
                                ['address_country' => $addressCountry]
                            );
                        }

                        $totalUpdated++;
                    } catch (\Exception $e) {
                        $this->warn("  Driver {$driverId}: failed to fetch details — {$e->getMessage()}");
                    }
                }

            } while ($startKey);

            $this->info("  Done — updated: {$totalUpdated}, skipped (already set): {$totalSkipped}");
        }

        return self::SUCCESS;
    }
}
