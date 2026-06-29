<?php

namespace App\Http\Controllers;

use App\Models\PayrollImport;
use App\Models\PayrollImportRow;
use App\Models\Person;
use App\Services\BankFileParser;
use App\Services\ImiPresenceLookup;
use App\Services\PayslipGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayrollImportController extends Controller
{
    public function index()
    {
        $imports = PayrollImport::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('payroll.imports.index', compact('imports'));
    }

    public function create()
    {
        return view('payroll.imports.create');
    }

    public function store(Request $request, BankFileParser $parser)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10 MB
            'payroll_month' => 'required|date',
            'is_payroll' => 'nullable|boolean',
        ]);

        $uploaded = $request->file('file');
        $path = $uploaded->store('payroll-imports/' . auth()->id(), 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $parsed = $parser->parse($fullPath);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return redirect()->back()->withInput()
                ->with('error', 'Failed to parse file: ' . $e->getMessage());
        }

        $import = DB::transaction(function () use ($validated, $request, $uploaded, $path, $parsed) {
            $import = PayrollImport::create([
                'user_id' => auth()->id(),
                'original_filename' => $uploaded->getClientOriginalName(),
                'stored_path' => $path,
                'account_number' => $parsed['meta']['account_number'] ?? null,
                'currency' => $parsed['meta']['currency'] ?? null,
                'payroll_month' => $validated['payroll_month'],
                'is_payroll' => $request->boolean('is_payroll', true),
                'status' => 'pending',
                'total_rows' => count($parsed['rows']),
            ]);

            // Pre-match against local persons by name (case-insensitive)
            $personLookup = $this->buildLocalPersonLookup();

            foreach ($parsed['rows'] as $r) {
                $matchedPersonId = null;
                if ($r['parsed_name']) {
                    $key = strtolower(trim($r['parsed_name']));
                    $matchedPersonId = $personLookup[$key] ?? null;
                }

                PayrollImportRow::create([
                    'payroll_import_id' => $import->id,
                    'row_index' => $r['row_index'],
                    'date' => $r['date'],
                    'value_date' => $r['value_date'],
                    'description' => $r['description'],
                    'debit' => $r['debit'],
                    'credit' => $r['credit'],
                    'balance' => $r['balance'],
                    'parsed_name' => $r['parsed_name'],
                    'reference' => $r['reference'],
                    'is_payroll' => $r['looks_like_payroll'],   // pre-check the likely ones
                    'looks_like_payroll' => $r['looks_like_payroll'],
                    'matched_person_id' => $matchedPersonId,
                ]);
            }

            return $import;
        });

        return redirect()->route('payroll-imports.review', $import->id)
            ->with('success', "Parsed {$import->total_rows} rows. Review and confirm payroll lines below.");
    }

    public function review(string $id, ImiPresenceLookup $lookup)
    {
        $import = PayrollImport::where('user_id', auth()->id())->findOrFail($id);
        $rows = $import->rows()->with('matchedPerson')->get();

        // For unmatched rows with a parsed_name, look them up cross-org in IMI
        $imiPresence = [];
        foreach ($rows as $row) {
            if (!$row->parsed_name || $row->matched_person_id) continue;
            $parts = preg_split('/\s+/', trim($row->parsed_name));
            $first = $parts[0] ?? '';
            $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
            $imiPresence[$row->id] = $lookup->findForName($first, $last);
        }

        return view('payroll.imports.review', compact('import', 'rows', 'imiPresence'));
    }

    /**
     * Persist the user's tweaks to the rows (which to import, name corrections, person matches).
     * Re-attempts the local-person lookup using the (possibly edited) name so corrected typos
     * actually associate with an existing Person without needing to "Create person" manually.
     */
    public function updateReview(Request $request, string $id)
    {
        $import = PayrollImport::where('user_id', auth()->id())->findOrFail($id);

        $rematched = $this->persistReviewState($import, $request);
        $import->update(['status' => 'reviewed']);

        $msg = 'Review saved.';
        if ($rematched > 0) {
            $msg .= " {$rematched} row(s) auto-matched to an existing person from your HR records.";
        }

        return redirect()->route('payroll-imports.review', $import->id)->with('success', $msg);
    }

    /**
     * Save the user's current review state (checkboxes, name corrections, person matches)
     * to the DB. Returns the count of rows that were auto-matched via name edits.
     *
     * Shared between updateReview() and generatePayslips() so that hitting "Generate Payslips"
     * directly always reflects the latest checkbox state without requiring a separate
     * "Save Review" click first.
     */
    private function persistReviewState(PayrollImport $import, Request $request): int
    {
        $request->validate([
            'rows' => 'array',
            'rows.*.is_payroll' => 'nullable|boolean',
            'rows.*.parsed_name' => 'nullable|string|max:255',
            'rows.*.matched_person_id' => 'nullable|integer|exists:persons,id',
        ]);

        $input = $request->input('rows', []);
        if (empty($input)) {
            return 0; // no row data submitted (e.g. raw "save" with empty body) — leave DB untouched
        }

        $personLookup = $this->buildLocalPersonLookup();
        $rematched = 0;

        foreach ($import->rows as $row) {
            $key = (string) $row->id;
            if (!isset($input[$key])) {
                $row->update(['is_payroll' => false]);
                continue;
            }

            $newName = $input[$key]['parsed_name'] ?? $row->parsed_name;
            $newMatch = $input[$key]['matched_person_id'] ?? $row->matched_person_id;

            if (empty($newMatch) && !empty($newName)) {
                $candidate = $personLookup[strtolower(trim($newName))] ?? null;
                if ($candidate) {
                    $newMatch = $candidate;
                    $rematched++;
                }
            }

            $row->update([
                'is_payroll' => (bool) ($input[$key]['is_payroll'] ?? false),
                'parsed_name' => $newName,
                'matched_person_id' => $newMatch,
            ]);
        }

        return $rematched;
    }

    /**
     * Bulk-create Person stubs for every ticked row that has a parsed_name but no
     * matched person yet. Lets the user go straight from upload → generate without
     * clicking "Create person" 11 times.
     */
    public function createAllMissingPersons(string $id)
    {
        $import = PayrollImport::where('user_id', auth()->id())->findOrFail($id);

        $rows = $import->rows()
            ->where('is_payroll', true)
            ->whereNull('matched_person_id')
            ->whereNotNull('parsed_name')
            ->where('parsed_name', '!=', '')
            ->get();

        if ($rows->isEmpty()) {
            return redirect()->route('payroll-imports.review', $import->id)
                ->with('info', 'No missing persons to create — every ticked row is already matched.');
        }

        $personLookup = $this->buildLocalPersonLookup();
        $created = 0;
        $matched = 0;

        foreach ($rows as $row) {
            $key = strtolower(trim($row->parsed_name));

            // Defensive: if a Person with that exact name was created during this loop, reuse it
            if (isset($personLookup[$key])) {
                $row->update(['matched_person_id' => $personLookup[$key]]);
                $matched++;
                continue;
            }

            $parts = preg_split('/\s+/', trim($row->parsed_name));
            $first = $parts[0] ?? $row->parsed_name;
            $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

            $person = Person::create([
                'user_id' => auth()->id(),
                'first_name' => $first,
                'last_name' => $last,
                'position' => 'Driver',
            ]);

            $row->update(['matched_person_id' => $person->id]);
            $personLookup[$key] = $person->id;
            $created++;
        }

        return redirect()->route('payroll-imports.review', $import->id)
            ->with('success', "Created {$created} person(s)" . ($matched > 0 ? " ({$matched} additionally auto-matched)" : '') . ". You can now generate payslips.");
    }

    public function destroy(string $id)
    {
        $import = PayrollImport::where('user_id', auth()->id())->findOrFail($id);
        $import->delete();
        return redirect()->route('payroll-imports.index')->with('success', 'Import deleted.');
    }

    /**
     * Create a stub Person from a single import row (uses parsed_name + IBAN if extractable).
     * Then auto-match the row to that Person.
     */
    public function createPersonFromRow(string $importId, string $rowId)
    {
        $import = PayrollImport::where('user_id', auth()->id())->findOrFail($importId);
        $row = $import->rows()->findOrFail($rowId);

        if (!$row->parsed_name) {
            return redirect()->back()->with('error', 'Cannot create a person: no name parsed from this row.');
        }

        $parts = preg_split('/\s+/', trim($row->parsed_name));
        $first = $parts[0] ?? $row->parsed_name;
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        $person = Person::create([
            'user_id' => auth()->id(),
            'first_name' => $first,
            'last_name' => $last,
            'position' => 'Driver',
        ]);

        $row->update(['matched_person_id' => $person->id]);

        return redirect()->route('payroll-imports.review', $import->id)
            ->with('success', "Created person “{$person->full_name}”. You can edit details from their profile later.");
    }

    /**
     * Generate Payslip records + PDFs for every ticked row that has a matched person.
     */
    public function generatePayslips(Request $request, string $id, PayslipGenerator $generator)
    {
        $import = PayrollImport::where('user_id', auth()->id())->findOrFail($id);

        // If row data was submitted (e.g. the Generate button is inside the review form),
        // persist the latest checkbox state before counting. This avoids the bug where
        // the user un-ticks rows visually but the DB still holds the stale state.
        if ($request->has('rows')) {
            $this->persistReviewState($import, $request);
        }

        $rows = $import->rows()->where('is_payroll', true)->get();

        if ($rows->isEmpty()) {
            return redirect()->route('payroll-imports.review', $import->id)
                ->with('error', 'No rows are ticked as payroll.');
        }

        $missingMatches = $rows->whereNull('matched_person_id');
        if ($missingMatches->isNotEmpty()) {
            $n = $missingMatches->count();
            return redirect()->route('payroll-imports.review', $import->id)
                ->with('error', "Cannot generate — {$n} ticked row(s) have no matched person. Click the green “Create {$n} missing person(s)” button above to create stubs for all of them at once, or edit individual names to match existing HR records.");
        }

        $created = 0;
        $errors = [];

        foreach ($rows as $row) {
            try {
                $person = Person::where('user_id', auth()->id())->findOrFail($row->matched_person_id);
                $payslip = $generator->fromImportRow($row, $person, $import);
                $generator->renderPdf($payslip, auth()->user());
                $created++;
            } catch (\Throwable $e) {
                \Log::warning('Payslip generation failed', ['row_id' => $row->id, 'error' => $e->getMessage()]);
                $errors[] = ($row->parsed_name ?? "Row #{$row->row_index}") . ': ' . $e->getMessage();
            }
        }

        $import->update([
            'status' => 'generated',
            'payslips_generated' => $created,
            'processed_at' => now(),
        ]);

        $msg = "{$created} payslip(s) generated.";
        if (!empty($errors)) {
            $msg .= ' Errors: ' . implode('; ', array_slice($errors, 0, 3));
        }

        return redirect()->route('payroll-imports.review', $import->id)->with('success', $msg);
    }

    // ---- helpers ----

    private function buildLocalPersonLookup(): array
    {
        $persons = Person::where('user_id', auth()->id())
            ->get(['id', 'first_name', 'last_name']);

        $map = [];
        foreach ($persons as $p) {
            $key = strtolower(trim($p->first_name . ' ' . $p->last_name));
            $map[$key] = $p->id;
        }
        return $map;
    }
}
