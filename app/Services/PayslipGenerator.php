<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\PayrollImport;
use App\Models\PayrollImportRow;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PayslipGenerator
{
    /**
     * Create (or refresh) a Payslip from a parsed bank-file row + matched person.
     *
     * @param  string|\DateTimeInterface|null  $generatedDateOverride  user-chosen date shown
     *         as the "Generated Date" on every payslip in a batch. Does NOT affect the
     *         Payment Date, which always reflects the actual bank transaction date.
     */
    public function fromImportRow(
        PayrollImportRow $row,
        Person $person,
        PayrollImport $import,
        $generatedDateOverride = null,
    ): Payslip {
        $transfer = (float) $row->debit;
        $perDiem = Payslip::calcPerDiem($transfer);
        $salary = Payslip::calcSalary($transfer);

        $payslip = Payslip::firstOrNew([
            'payroll_import_row_id' => $row->id,
            'person_id' => $person->id,
        ]);

        $payslip->fill([
            'user_id' => $import->user_id,
            'payroll_import_id' => $import->id,
            'employee_name' => $person->full_name,
            'position' => $person->position ?? 'Driver',
            'bank_iban' => $person->bank_iban,
            'bank_swift' => $person->bank_swift,
            'payroll_month' => $import->payroll_month,
            'payment_date' => $row->date ?? $import->payroll_month,
            'generated_date' => $generatedDateOverride,
            'currency' => $import->currency ?: 'EUR',
            'transfer_amount' => $transfer,
            'gross_salary' => $salary,
            'per_diem' => $perDiem,
            'income_tax' => 0,
            'social_insurance' => 0,
            'ghs' => 0,
            'other_deductions' => 0,
            'net_salary' => $salary,
        ]);
        $payslip->save();

        return $payslip;
    }

    /**
     * Render the payslip PDF and store it on the local disk. Returns the storage path.
     */
    public function renderPdf(Payslip $payslip, User $companyUser): string
    {
        $company = $companyUser->getCompanyHeader();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip,
            'company' => $company,
        ])->setPaper('a4');

        $folder = "payslips/{$payslip->user_id}/" . $payslip->payroll_month->format('Y-m');
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $payslip->employee_name);
        $filename = "Payslip_{$safeName}_" . $payslip->payroll_month->format('Y-m') . "_{$payslip->id}.pdf";
        $path = "{$folder}/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        $payslip->update([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ]);

        return $path;
    }
}
