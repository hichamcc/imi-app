<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\MaponService;
use App\Services\PostingApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin tool — cross-reference drivers with active IMI declarations against MAPON
 * tachograph activity. Flags drivers whose most recent tacho activity is older than
 * 1 month (or missing), suggesting the IMI declaration is stale / driver no longer active.
 */
class TachoActivityController extends Controller
{
    public function __construct(
        protected DriverService $driverService,
        protected DeclarationService $declarationService,
        protected PostingApiService $apiService,
        protected MaponService $maponService,
    ) {}

    /**
     * Withdraw every active SUBMITTED declaration for the given driver.
     * Called from the Tacho Activity report for drivers flagged "inactive" (no tacho
     * activity in the window). The declarations are withdrawn one-by-one; a per-call
     * failure doesn't abort the rest.
     */
    public function withdrawDriver(Request $request, string $driverId)
    {
        $userId = (int) $request->input('user_id', 0);
        if ($userId <= 0) {
            return redirect()->route('admin.tacho-activity.index')
                ->with('error', 'Missing user_id — cannot determine which org to act on.');
        }

        $owner = User::find($userId);
        if (!$owner || !$owner->hasValidApiCredentials()) {
            return redirect()->route('admin.tacho-activity.index', ['user_id' => $userId])
                ->with('error', 'Selected organisation has incomplete IMI API credentials.');
        }
        $this->apiService->setUserCredentials($owner->api_base_url, $owner->api_key, $owner->api_operator_id);

        $today = Carbon::today()->format('Y-m-d');
        $withdrawn = 0;
        $errors = [];

        // Fetch all active SUBMITTED declarations for this driver, across pages.
        $toWithdraw = [];
        $startKey = null;
        do {
            $batch = $this->declarationService->getDeclarationsPaginated(250, $startKey, ['driverId' => $driverId]);
            foreach ($batch['items'] ?? [] as $d) {
                if (strtoupper($d['declarationStatus'] ?? '') !== 'SUBMITTED') continue;
                if (($d['declarationEndDate'] ?? '') < $today) continue;
                $toWithdraw[] = $d;
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);

        foreach ($toWithdraw as $d) {
            $declarationId = $d['declarationId'];
            try {
                $this->declarationService->withdrawDeclaration($declarationId);
                $withdrawn++;
                \Log::info('Tacho report: withdrew declaration', [
                    'admin_user_id' => auth()->id(),
                    'org_user_id' => $userId,
                    'driver_id' => $driverId,
                    'declaration_id' => $declarationId,
                    'country' => $d['declarationPostingCountry'] ?? null,
                ]);
            } catch (\Throwable $e) {
                $errors[] = ($d['declarationPostingCountry'] ?? '??') . ': ' . $e->getMessage();
                \Log::warning('Tacho report: withdraw failed', [
                    'declaration_id' => $declarationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $msg = "Withdrew {$withdrawn} of " . count($toWithdraw) . " declaration(s) for driver.";
        if (!empty($errors)) $msg .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '…' : '');

        return redirect()->route('admin.tacho-activity.index', ['user_id' => $userId])
            ->with(empty($errors) ? 'success' : 'error', $msg);
    }

    public function index(Request $request)
    {
        $apiUsers = User::whereNotNull('api_key')
            ->whereNotNull('api_operator_id')
            ->whereNotNull('api_base_url')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedUserId = $request->integer('user_id') ?: (auth()->user()->hasValidApiCredentials() ? auth()->id() : ($apiUsers->first()->id ?? null));
        $windowDays = (int) ($request->get('window_days', 30));
        if ($windowDays < 1 || $windowDays > 365) $windowDays = 30;

        $report = ['drivers' => [], 'error' => null, 'stats' => null];
        if ($selectedUserId) {
            try {
                $report = $this->buildReport((int) $selectedUserId, $windowDays);
            } catch (\Throwable $e) {
                $report['error'] = $e->getMessage();
            }
        }

        return view('admin.tacho-activity.index', [
            'apiUsers' => $apiUsers,
            'selectedUserId' => $selectedUserId,
            'windowDays' => $windowDays,
            'drivers' => $report['drivers'],
            'stats' => $report['stats'],
            'error' => $report['error'],
            'maponConfigured' => $this->maponService->isConfigured(),
        ]);
    }

    private function buildReport(int $userId, int $windowDays): array
    {
        if (!$this->maponService->isConfigured()) {
            return ['drivers' => [], 'stats' => null, 'error' => 'MAPON_API_KEY is not configured in .env.'];
        }

        $owner = User::findOrFail($userId);
        if (!$owner->hasValidApiCredentials()) {
            return ['drivers' => [], 'stats' => null, 'error' => "{$owner->name} has incomplete IMI API credentials."];
        }
        $this->apiService->setUserCredentials($owner->api_base_url, $owner->api_key, $owner->api_operator_id);

        $today = Carbon::today();
        $todayStr = $today->format('Y-m-d');
        $fromStr = $today->copy()->subDays($windowDays)->format('Y-m-d');

        // 1. All IMI drivers for this org.
        $imiDrivers = $this->fetchAllImiDrivers();

        // 2. All declarations → map driver name → list of active (SUBMITTED, endDate >= today) countries.
        $activeByName = $this->activeCountriesByDriverName($todayStr);

        // 3. MAPON drivers → normalized full name → mapon driver record.
        // Normalization strips diacritics + lowercases + collapses whitespace so that
        // "Aidas Vaičius" (MAPON) matches "AIDAS VAICIUS" (IMI).
        $maponByName = [];
        foreach ($this->maponService->listDrivers() as $m) {
            $name = $this->maponDriverName($m);
            if ($name === '') continue;
            $maponByName[$this->normalizeName($name)] = $m;
        }

        $rows = [];
        $stats = ['active' => 0, 'stale' => 0, 'no_mapon' => 0, 'checked' => 0];

        foreach ($imiDrivers as $driver) {
            $fullName = trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? ''));
            $key = $this->normalizeName($fullName);

            $activeCountries = $activeByName[$key] ?? [];
            if (empty($activeCountries)) continue; // only drivers WITH active declarations
            $stats['checked']++;

            $maponDriver = $maponByName[$key] ?? null;
            $maponId = $maponDriver ? ($maponDriver['id'] ?? $maponDriver['driver_id'] ?? null) : null;

            if (!$maponId) {
                $rows[] = [
                    'name' => $fullName,
                    'driver_id' => $driver['driverId'] ?? null,
                    'active_countries' => $activeCountries,
                    'mapon_matched' => false,
                    'last_activity' => null,
                    'days_since' => null,
                    'status' => 'no_mapon',
                ];
                $stats['no_mapon']++;
                continue;
            }

            $lastActivity = null;
            try {
                $lastActivity = $this->maponService->latestActivityDate($maponId, $fromStr, $todayStr);
            } catch (\Throwable $e) {
                // Record the failure but keep going — don't let one broken driver kill the report.
                \Log::warning('MAPON daily_activities failed', [
                    'driver' => $fullName,
                    'mapon_id' => $maponId,
                    'error' => $e->getMessage(),
                ]);
            }

            $daysSince = $lastActivity ? $today->diffInDays(Carbon::parse($lastActivity)) : null;
            $isStale = $lastActivity === null || $daysSince > $windowDays;

            $rows[] = [
                'name' => $fullName,
                'driver_id' => $driver['driverId'] ?? null,
                'active_countries' => $activeCountries,
                'mapon_matched' => true,
                'last_activity' => $lastActivity,
                'days_since' => $daysSince,
                'status' => $isStale ? 'stale' : 'active',
            ];
            if ($isStale) $stats['stale']++;
            else $stats['active']++;
        }

        // Sort: stale first, then no_mapon, then active — most actionable at top.
        $priority = ['stale' => 0, 'no_mapon' => 1, 'active' => 2];
        usort($rows, function ($a, $b) use ($priority) {
            $pa = $priority[$a['status']] ?? 9;
            $pb = $priority[$b['status']] ?? 9;
            if ($pa !== $pb) return $pa <=> $pb;
            return strcmp($a['name'], $b['name']);
        });

        return ['drivers' => $rows, 'stats' => $stats, 'error' => null];
    }

    // ---- helpers ----

    private function fetchAllImiDrivers(): array
    {
        $all = [];
        $startKey = null;
        do {
            $batch = $this->driverService->getDriversPaginated(250, $startKey);
            $all = array_merge($all, $batch['items'] ?? []);
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);
        return $all;
    }

    /**
     * Map lowercase "firstname lastname" → unique list of country codes where the driver
     * currently has an active SUBMITTED declaration (endDate >= today).
     */
    private function activeCountriesByDriverName(string $today): array
    {
        $byName = [];
        $startKey = null;
        do {
            $batch = $this->declarationService->getDeclarationsPaginated(250, $startKey);
            foreach ($batch['items'] ?? [] as $d) {
                if (strtoupper($d['declarationStatus'] ?? '') !== 'SUBMITTED') continue;
                if (($d['declarationEndDate'] ?? '') < $today) continue;

                $name = $this->normalizeName($d['driverLatinFullName'] ?? '');
                $country = strtoupper($d['declarationPostingCountry'] ?? '');
                if ($name === '' || $country === '') continue;

                $byName[$name][$country] = true;
            }
            $startKey = $batch['lastEvaluatedKey'] ?? null;
        } while ($startKey);

        // De-dupe: keep lists of countries, not maps
        return array_map(fn($set) => array_keys($set), $byName);
    }

    /**
     * Normalise a human name for matching across sources (MAPON vs IMI).
     * - lowercase
     * - strip diacritics (č→c, á→a, ö→o, …) via iconv transliteration
     * - collapse all whitespace to single spaces
     * Example: "Aidas Vaičius" and "AIDAS VAICIUS" both become "aidas vaicius".
     */
    private function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') return '';

        // iconv transliteration — needs the "en_US.UTF-8" locale for the ASCII//TRANSLIT path.
        $prev = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'en_US.UTF-8', 'C.UTF-8', 'C');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        setlocale(LC_CTYPE, $prev);

        if ($ascii === false || $ascii === null) $ascii = $name;

        // Convert separators (hyphen, underscore, dot) to space so "Jean-François" and
        // "Jean Francois" normalize the same.
        $ascii = preg_replace('/[-_.]/', ' ', $ascii);
        // iconv//TRANSLIT sometimes emits things like "c'" for č — drop remaining punctuation.
        $ascii = preg_replace('/[^A-Za-z\s]/', '', $ascii);
        $ascii = preg_replace('/\s+/', ' ', $ascii);
        return strtolower(trim($ascii));
    }

    /**
     * Extract a full name from a MAPON driver record (shapes vary).
     * MAPON's primary shape is {name: <first>, surname: <last>} — treat those as first/last,
     * not full name. Fall back to common first/last variants, then to a bare `full_name`.
     */
    private function maponDriverName(array $m): string
    {
        $first = $m['name']
            ?? $m['first_name']
            ?? $m['firstname']
            ?? $m['first']
            ?? '';
        $last = $m['surname']
            ?? $m['last_name']
            ?? $m['lastname']
            ?? $m['last']
            ?? '';

        $combined = trim("{$first} {$last}");
        if ($combined !== '') return $combined;

        return trim($m['full_name'] ?? '');
    }
}
