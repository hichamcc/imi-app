<?php

namespace App\Console\Commands;

use App\Services\PostingApiService;
use App\Services\DeclarationService;
use App\Services\PlateNumberService;
use App\Models\DriverProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoSubmitExpiredDeclarations extends Command
{
    protected $signature = 'declarations:auto-submit
                            {--since= : Renew declarations that expired on or after this date (YYYY-MM-DD). Default: yesterday only.}
                            {--dry-run : List what would be renewed without calling the API.}';
    protected $description = 'Automatically create and submit new declarations for drivers with expired declarations. Defaults to declarations that ended yesterday; use --since=YYYY-MM-DD to catch up on missed renewals.';

    protected PostingApiService $apiService;
    protected DeclarationService $declarationService;
    protected PlateNumberService $plateNumberService;

    /** Cache of plate → 'LIGHT' | 'HEAVY' | 'PASSENGERS' for the current user's register. */
    protected array $plateWeightMap = [];

    public function __construct(PostingApiService $apiService, DeclarationService $declarationService, PlateNumberService $plateNumberService)
    {
        parent::__construct();
        $this->apiService = $apiService;
        $this->declarationService = $declarationService;
        $this->plateNumberService = $plateNumberService;
    }

    public function handle()
    {
        $startTime = microtime(true);
        $currentDateTime = now()->toDateTimeString();
        $this->info("Starting auto-submit process for expired declarations at {$currentDateTime}...");

        // Log the start of the process
        Log::info('AUTO-SUBMIT: Process started', [
            'started_at' => $currentDateTime,
            'yesterday' => Carbon::yesterday()->format('Y-m-d'),
            'today' => Carbon::today()->format('Y-m-d'),
            'process_id' => getmypid(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ]);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $sinceDate = $this->option('since') ? Carbon::parse($this->option('since'))->format('Y-m-d') : $yesterday;
        $dryRun = (bool) $this->option('dry-run');

        if ($sinceDate !== $yesterday) {
            $this->warn("Range mode: renewing declarations expired between {$sinceDate} and {$yesterday} (inclusive). Duplicates per driver+country will be deduped, keeping only the latest.");
        }
        if ($dryRun) {
            $this->warn('DRY RUN — no API create/submit calls will be made.');
        }

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

                    // Load the user's plate-number register once per user so we can split
                    // legacy `declarationVehiclePlateNumber` values into LIGHT/HEAVY for goods.
                    $this->loadPlateWeightMap();

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
                        // Dedupe: if the same driver + country has multiple expired declarations
                        // in the range (e.g. a chain of failed renewals), keep only the one with
                        // the latest end_date so we don't create duplicates.
                        $candidates = [];
                        foreach ($declarations['items'] as $declaration) {
                            if (!$this->isWithinExpiredRange($declaration, $sinceDate, $yesterday)) {
                                continue;
                            }
                            $dedupeKey = ($declaration['driverId'] ?? 'no-driver') . '|' . ($declaration['declarationPostingCountry'] ?? '');
                            $endDate = $declaration['declarationEndDate'] ?? '';
                            if (!isset($candidates[$dedupeKey]) || $endDate > ($candidates[$dedupeKey]['declarationEndDate'] ?? '')) {
                                $candidates[$dedupeKey] = $declaration;
                            }
                        }

                        foreach ($candidates as $declaration) {
                            $expiredCount++;
                            $this->line("  - Found expired declaration: {$declaration['declarationId']} (End date: {$declaration['declarationEndDate']}, Country: {$declaration['declarationPostingCountry']})");

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

                                    // Pre-check: warn about plates in the payload that aren't in the local plate register.
                                    // The API will reject the declaration if any plate is unknown to /plate-numbers.
                                    $payloadPlates = array_merge(
                                        $newDeclarationData['declarationVehiclePlateNumber'] ?? [],
                                        $newDeclarationData['declarationVehiclePlateNumberLight'] ?? [],
                                        $newDeclarationData['declarationVehiclePlateNumberHeavy'] ?? [],
                                    );
                                    $missingPlates = array_values(array_filter($payloadPlates, fn($p) => !isset($this->plateWeightMap[$p])));
                                    if (!empty($missingPlates)) {
                                        $this->warn('    ⚠ Plates NOT in /plate-numbers register (declaration will likely be rejected): ' . implode(', ', $missingPlates));
                                        Log::warning('AUTO-SUBMIT: Unregistered plates on declaration payload', [
                                            'source_declaration_id' => $declaration['declarationId'],
                                            'driver_id' => $driverId,
                                            'country' => $newDeclarationData['declarationPostingCountry'],
                                            'missing_plates' => $missingPlates,
                                        ]);
                                    }

                                    if ($dryRun) {
                                        $this->info("    [dry-run] Would POST /declarations for driver {$driverId}, country {$newDeclarationData['declarationPostingCountry']}");
                                        $createdCount++;
                                        $submittedCount++;
                                        continue;
                                    }

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
     * Load the currently-authed user's plate register into an in-memory map so
     * prepareNewDeclarationData() can split legacy plate lists into LIGHT vs HEAVY.
     * Safe to fail — falls back to an empty map, in which case unknown plates default to HEAVY.
     */
    private function loadPlateWeightMap(): void
    {
        $this->plateWeightMap = [];
        try {
            $items = $this->plateNumberService->all();
            foreach ($items as $p) {
                $plate = $p['plateNumber'] ?? null;
                if (!$plate) continue;

                $type = $p['plateNumberTransportType'] ?? $p['transportType'] ?? null;
                $weight = $p['vehicleWeight'] ?? '';

                if ($type === 'CARRIAGE_OF_PASSENGERS') {
                    $this->plateWeightMap[$plate] = 'PASSENGERS';
                } elseif ($type === 'CARRIAGE_OF_GOODS') {
                    $this->plateWeightMap[$plate] = $weight === 'LIGHT' ? 'LIGHT' : 'HEAVY';
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AUTO-SUBMIT: Failed to load plate register — legacy plates will default to HEAVY', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * True if the declaration expired between $sinceDate and $yesterday (inclusive)
     * and is in a status we can renew from (EXPIRED or SUBMITTED).
     */
    private function isWithinExpiredRange(array $declaration, string $sinceDate, string $yesterday): bool
    {
        if (empty($declaration['declarationEndDate'])) {
            return false;
        }

        $endDate = Carbon::parse($declaration['declarationEndDate'])->format('Y-m-d');
        if ($endDate < $sinceDate || $endDate > $yesterday) {
            return false;
        }

        $status = strtoupper($declaration['declarationStatus'] ?? '');
        return $status === 'EXPIRED' || $status === 'SUBMITTED';
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
            'driverId' => $originalDeclaration['driverId'],
            'otherContactAsTransportManager' => $otherContactAsTransportManager,
        ];

        // Plate fields — the RTPD API (post 2026-06-30) requires goods declarations to use
        // declarationVehiclePlateNumberLight / Heavy. Only passengers still use the legacy
        // declarationVehiclePlateNumber. Old expired declarations that predate this change
        // still carry all their plates in the legacy field, so we look them up in the
        // /plate-numbers register and split them into the correct buckets here.
        $transportTypes = $newData['declarationTransportType'] ?? [];
        $isGoods = in_array('CARRIAGE_OF_GOODS', $transportTypes, true);
        $isPassengers = in_array('CARRIAGE_OF_PASSENGERS', $transportTypes, true);

        // Collect every plate the source declaration referenced (across all three fields).
        $allPlates = array_unique(array_merge(
            $originalDeclaration['declarationVehiclePlateNumber'] ?? [],
            $originalDeclaration['declarationVehiclePlateNumberLight'] ?? [],
            $originalDeclaration['declarationVehiclePlateNumberHeavy'] ?? [],
        ));

        if ($isGoods) {
            $light = [];
            $heavy = [];
            foreach ($allPlates as $plate) {
                $weight = $this->plateWeightMap[$plate] ?? null;
                if ($weight === 'LIGHT') {
                    $light[] = $plate;
                } elseif ($weight === 'HEAVY') {
                    $heavy[] = $plate;
                } elseif ($weight === 'PASSENGERS') {
                    // Registered as passengers only — can't be used for goods declarations
                    continue;
                } else {
                    // Not found in register — default to HEAVY (most freight fleets) so the
                    // API can still validate. If it doesn't exist in the register the API
                    // will reject the whole declaration and log it either way.
                    $heavy[] = $plate;
                }
            }
            if (!empty($light)) $newData['declarationVehiclePlateNumberLight'] = array_values(array_unique($light));
            if (!empty($heavy)) $newData['declarationVehiclePlateNumberHeavy'] = array_values(array_unique($heavy));
        }

        if ($isPassengers) {
            $passengers = [];
            foreach ($allPlates as $plate) {
                $weight = $this->plateWeightMap[$plate] ?? null;
                // Include plates registered as passengers, plus unregistered plates as a
                // fallback (same rationale as above).
                if ($weight === 'PASSENGERS' || $weight === null) {
                    $passengers[] = $plate;
                }
            }
            if (!empty($passengers)) $newData['declarationVehiclePlateNumber'] = array_values(array_unique($passengers));
        }

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