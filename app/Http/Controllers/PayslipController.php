<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Services\PayslipGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $payslips = $query->paginate(25)->withQueryString();

        // Count of matching rows for the "Download N as ZIP" button label
        $totalMatching = $this->filteredQuery($request)->count();

        return view('payroll.payslips.index', compact('payslips', 'totalMatching'));
    }

    /**
     * Stream a ZIP of every payslip PDF matching the current filters
     * (employee search + month + payment_date range). Missing PDF files
     * are regenerated on the fly so the ZIP is always complete.
     */
    public function downloadZip(Request $request, PayslipGenerator $generator)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $payslips = $this->filteredQuery($request)->orderBy('payment_date')->get();

        if ($payslips->isEmpty()) {
            return redirect()->route('payslips.index')->with('error', 'No payslips match the current filters.');
        }

        if (!class_exists(\ZipArchive::class)) {
            return redirect()->route('payslips.index')->with('error', 'PHP ZipArchive extension is not available on this server.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'payslips_') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('payslips.index')->with('error', 'Could not create the ZIP file.');
        }

        $companyUser = auth()->user();
        foreach ($payslips as $p) {
            // Regenerate the PDF if it's missing from disk
            if (!$p->pdf_path || !Storage::disk('local')->exists($p->pdf_path)) {
                try {
                    $generator->renderPdf($p->fresh(), $companyUser);
                    $p->refresh();
                } catch (\Throwable $e) {
                    \Log::warning('Payslip PDF regeneration failed during ZIP', ['payslip_id' => $p->id, 'error' => $e->getMessage()]);
                    continue;
                }
            }

            if (!$p->pdf_path || !Storage::disk('local')->exists($p->pdf_path)) {
                continue; // still missing — skip
            }

            $entryName = sprintf(
                'Payslip_%s_%s_%d.pdf',
                str_replace(' ', '_', $p->employee_name),
                $p->payment_date->format('Y-m-d'),
                $p->id,
            );
            $zip->addFromString($entryName, Storage::disk('local')->get($p->pdf_path));
        }
        $zip->close();

        $filename = $this->zipFilename($request);

        return response()->streamDownload(function () use ($tmpPath) {
            readfile($tmpPath);
            @unlink($tmpPath);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Shared filter logic for the index list + the ZIP download.
     */
    private function filteredQuery(Request $request)
    {
        $query = Payslip::where('user_id', auth()->id())
            ->with('person')
            ->latest('payment_date')
            ->latest('id');

        if ($month = $request->get('month')) {
            try {
                $start = \Carbon\Carbon::parse($month . '-01');
                $query->whereBetween('payroll_month', [$start->copy()->startOfMonth(), $start->copy()->endOfMonth()]);
            } catch (\Throwable) {}
        }

        // Date range — applied to payment_date (the bank transaction date)
        if ($from = $request->get('from')) {
            try {
                $query->where('payment_date', '>=', \Carbon\Carbon::parse($from)->startOfDay());
            } catch (\Throwable) {}
        }
        if ($to = $request->get('to')) {
            try {
                $query->where('payment_date', '<=', \Carbon\Carbon::parse($to)->endOfDay());
            } catch (\Throwable) {}
        }

        if ($term = $request->get('search')) {
            $query->where('employee_name', 'like', "%{$term}%");
        }

        return $query;
    }

    private function zipFilename(Request $request): string
    {
        $parts = ['payslips'];
        if ($request->filled('from')) $parts[] = 'from-' . $request->input('from');
        if ($request->filled('to')) $parts[] = 'to-' . $request->input('to');
        if ($request->filled('month')) $parts[] = $request->input('month');
        return implode('_', $parts) . '.zip';
    }

    public function download(string $id)
    {
        $payslip = Payslip::where('user_id', auth()->id())->findOrFail($id);

        if (!$payslip->pdf_path || !Storage::disk('local')->exists($payslip->pdf_path)) {
            abort(404, 'Payslip PDF not found. Try regenerating it.');
        }

        $filename = 'Payslip_' . str_replace(' ', '_', $payslip->employee_name) . '_' . $payslip->payroll_month->format('Y-m') . '.pdf';
        return Storage::disk('local')->download($payslip->pdf_path, $filename);
    }

    public function view(string $id)
    {
        $payslip = Payslip::where('user_id', auth()->id())->findOrFail($id);

        if (!$payslip->pdf_path || !Storage::disk('local')->exists($payslip->pdf_path)) {
            abort(404, 'Payslip PDF not found. Try regenerating it.');
        }

        return response(Storage::disk('local')->get($payslip->pdf_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payslip.pdf"',
        ]);
    }

    public function regenerate(string $id, PayslipGenerator $generator)
    {
        $payslip = Payslip::where('user_id', auth()->id())->findOrFail($id);
        $generator->renderPdf($payslip, auth()->user());
        return redirect()->back()->with('success', 'PDF regenerated.');
    }

    public function destroy(string $id)
    {
        $payslip = Payslip::where('user_id', auth()->id())->findOrFail($id);
        $payslip->delete();
        return redirect()->back()->with('success', 'Payslip deleted.');
    }
}
