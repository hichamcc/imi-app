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
        $query = Payslip::where('user_id', auth()->id())
            ->with('person')
            ->latest('payroll_month')
            ->latest('id');

        if ($month = $request->get('month')) {
            try {
                $start = \Carbon\Carbon::parse($month . '-01');
                $query->whereBetween('payroll_month', [$start->copy()->startOfMonth(), $start->copy()->endOfMonth()]);
            } catch (\Throwable) {}
        }

        if ($term = $request->get('search')) {
            $query->where('employee_name', 'like', "%{$term}%");
        }

        $payslips = $query->paginate(25)->withQueryString();

        return view('payroll.payslips.index', compact('payslips'));
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
