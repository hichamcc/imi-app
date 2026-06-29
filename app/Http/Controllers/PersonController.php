<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\PersonFile;
use App\Services\DriverService;
use App\Services\ImiPresenceLookup;
use App\Services\PersonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function __construct(protected PersonService $personService) {}

    public function index(Request $request, ImiPresenceLookup $lookup)
    {
        $query = Person::where('user_id', auth()->id())
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($term = $request->get('search')) {
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $persons = $query->paginate(20)->withQueryString();

        // Build IMI presence map for visible persons only
        $imiPresence = [];
        $lookupError = null;
        try {
            foreach ($persons as $person) {
                $dob = $person->date_of_birth?->format('Y-m-d');
                $imiPresence[$person->id] = $lookup->findForName(
                    $person->first_name,
                    $person->last_name,
                    $dob,
                );
            }
        } catch (\Throwable $e) {
            $lookupError = 'IMI presence lookup partially failed: ' . $e->getMessage();
        }

        return view('persons.index', compact('persons', 'imiPresence', 'lookupError'));
    }

    public function refreshImiPresence(ImiPresenceLookup $lookup)
    {
        $lookup->bust();
        return redirect()->route('persons.index')->with('success', 'IMI presence cache refreshed.');
    }

    public function linkToImiDriver(Request $request, string $id)
    {
        $request->validate([
            'driver_id' => 'required|string',
            'company_user_id' => 'required|exists:users,id',
        ]);

        $person = Person::where('user_id', auth()->id())->findOrFail($id);

        $person->update([
            'imi_driver_id' => $request->input('driver_id'),
            'imi_user_id' => $request->input('company_user_id'),
        ]);

        return redirect()->back()->with('success', 'Linked to IMI driver.');
    }

    public function create()
    {
        return view('persons.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validatePerson($request);
        $validated['user_id'] = auth()->id();

        $person = Person::create($validated);

        if ($request->boolean('also_create_in_imi')) {
            $result = $this->personService->syncToImi($person);
            if ($result['success']) {
                return redirect()->route('persons.show', $person->id)
                    ->with('success', 'Person created and synced to IMI (driver ID: ' . $result['driver_id'] . ').');
            }
            return redirect()->route('persons.show', $person->id)
                ->with('warning', 'Person created locally, but IMI sync failed: ' . $result['error']);
        }

        return redirect()->route('persons.show', $person->id)->with('success', 'Person created.');
    }

    /**
     * Show the IMI driver list with import buttons.
     */
    public function importFromImiIndex(Request $request, DriverService $driverService)
    {
        $startKey = $request->get('startKey');
        try {
            $result = $driverService->getDriversPaginated(50, $startKey);
            $drivers = $result['items'] ?? $result ?? [];
            $nextKey = $result['lastEvaluatedKey'] ?? null;
        } catch (\Throwable $e) {
            return redirect()->route('persons.index')
                ->with('error', 'Failed to load IMI drivers: ' . $e->getMessage());
        }

        // Map imi_driver_id → local Person for "already imported" badge
        $driverIds = collect($drivers)->pluck('driverId')->filter()->values();
        $linkedByDriverId = Person::where('user_id', auth()->id())
            ->whereIn('imi_driver_id', $driverIds)
            ->get(['id', 'imi_driver_id'])
            ->keyBy('imi_driver_id');

        return view('persons.import_from_imi', compact('drivers', 'nextKey', 'startKey', 'linkedByDriverId'));
    }

    public function importFromImiOne(Request $request)
    {
        $request->validate(['driver_id' => 'required|string']);
        $result = $this->personService->importFromImi($request->input('driver_id'));

        if ($result['success']) {
            $msg = $result['created']
                ? 'Imported driver into HR.'
                : 'Driver was already imported.';
            return redirect()->route('persons.show', $result['person']->id)->with('success', $msg);
        }

        return redirect()->back()->with('error', 'Import failed: ' . $result['error']);
    }

    public function importFromImiBulk(Request $request, DriverService $driverService)
    {
        $created = 0;
        $skipped = 0;
        $failed = 0;
        $startKey = null;

        // Walk all pages once
        do {
            try {
                $params = ['limit' => 250];
                if ($startKey) $params['startKey'] = $startKey;
                $page = $driverService->getDrivers($params);
            } catch (\Throwable $e) {
                return redirect()->route('persons.import-from-imi')
                    ->with('error', 'Bulk import aborted: ' . $e->getMessage());
            }

            $batch = $page['items'] ?? $page ?? [];
            $startKey = $page['lastEvaluatedKey'] ?? null;

            foreach ($batch as $driver) {
                $driverId = $driver['driverId'] ?? null;
                if (!$driverId) continue;

                $result = $this->personService->importFromImi($driverId);
                if ($result['success'] && $result['created']) $created++;
                elseif ($result['success'] && !$result['created']) $skipped++;
                else $failed++;
            }
        } while ($startKey);

        return redirect()->route('persons.import-from-imi')
            ->with('success', "Bulk import complete — {$created} imported, {$skipped} already linked, {$failed} failed.");
    }

    public function syncToImi(string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $result = $this->personService->syncToImi($person);

        if ($result['success']) {
            return redirect()->route('persons.show', $person->id)
                ->with('success', 'Synced to IMI. Driver ID: ' . $result['driver_id']);
        }

        return redirect()->route('persons.show', $person->id)
            ->with('error', 'IMI sync failed: ' . $result['error']);
    }

    public function show(string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $files = $person->files()->latest()->get();
        $payslips = $person->payslips()->get();
        return view('persons.show', compact('person', 'files', 'payslips'));
    }

    public function edit(string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        return view('persons.edit', array_merge(['person' => $person], $this->formData()));
    }

    public function update(Request $request, string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $validated = $this->validatePerson($request);
        $person->update($validated);
        return redirect()->route('persons.show', $person->id)->with('success', 'Person updated.');
    }

    public function destroy(string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $person->files()->get()->each->delete();
        $person->delete();
        return redirect()->route('persons.index')->with('success', 'Person deleted.');
    }

    // ---- File archive ----

    public function uploadFile(Request $request, string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'file' => 'required|file|max:20480', // 20 MB
            'label' => 'nullable|string|max:255',
        ]);

        $uploaded = $request->file('file');
        $path = $uploaded->store("persons/{$person->id}", 'local');

        PersonFile::create([
            'person_id' => $person->id,
            'uploaded_by' => auth()->id(),
            'original_name' => $uploaded->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploaded->getMimeType(),
            'size_bytes' => $uploaded->getSize(),
            'label' => $request->input('label'),
        ]);

        return redirect()->route('persons.show', $person->id)->with('success', 'File uploaded.');
    }

    public function downloadFile(string $id, string $fileId)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $file = $person->files()->findOrFail($fileId);

        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, 'File missing on disk.');
        }

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function deleteFile(string $id, string $fileId)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $file = $person->files()->findOrFail($fileId);
        $file->delete();
        return redirect()->route('persons.show', $person->id)->with('success', 'File deleted.');
    }

    /**
     * Generate the employment agreement PDF for download.
     * Accepts an optional `notes` field that will be rendered into the document.
     */
    public function generateContract(Request $request, string $id)
    {
        $person = Person::where('user_id', auth()->id())->findOrFail($id);
        $company = auth()->user()->getCompanyHeader();

        $contractNotes = $request->input('notes');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.employment_contract', compact('person', 'company', 'contractNotes'))
            ->setPaper('a4');

        $filename = 'employment-agreement-' . str_replace(' ', '_', strtolower($person->full_name)) . '.pdf';

        return $pdf->download($filename);
    }

    // ---- Helpers ----

    private function formData(): array
    {
        return [
            'documentTypes' => [
                'IDCARD' => 'ID Card',
                'PASSPORT' => 'Passport',
                'DRIVINGLICENSE' => 'Driving License',
            ],
            'countries' => [
                'AM' => 'Armenia', 'AT' => 'Austria', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BG' => 'Bulgaria',
                'HR' => 'Croatia', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EE' => 'Estonia',
                'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece', 'HU' => 'Hungary',
                'IN' => 'India', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia', 'LT' => 'Lithuania',
                'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands', 'PH' => 'Philippines', 'PL' => 'Poland',
                'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'ES' => 'Spain',
                'LK' => 'Sri Lanka', 'SE' => 'Sweden', 'UA' => 'Ukraine', 'ZW' => 'Zimbabwe',
            ],
        ];
    }

    private function validatePerson(Request $request): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'document_type' => ['nullable', Rule::in(['IDCARD', 'PASSPORT', 'DRIVINGLICENSE'])],
            'document_number' => 'nullable|string|max:50',
            'document_issuing_country' => 'nullable|string|size:2',
            'license_number' => 'nullable|string|max:50',
            'address_street' => 'nullable|string|max:255',
            'address_post_code' => 'nullable|string|max:20',
            'address_city' => 'nullable|string|max:100',
            'address_country' => 'nullable|string|size:2',
            'contract_start_date' => 'nullable|date',
            'applicable_law' => 'nullable|string|size:2',
            'position' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'bank_iban' => 'nullable|string|max:50',
            'bank_swift' => 'nullable|string|max:20',
            'monthly_salary' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);
    }
}
