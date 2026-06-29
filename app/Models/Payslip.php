<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payslip extends Model
{
    protected $fillable = [
        'user_id',
        'person_id',
        'payroll_import_id',
        'payroll_import_row_id',
        'employee_name',
        'position',
        'bank_iban',
        'bank_swift',
        'payroll_month',
        'payment_date',
        'generated_date',
        'currency',
        'transfer_amount',
        'gross_salary',
        'per_diem',
        'income_tax',
        'social_insurance',
        'ghs',
        'other_deductions',
        'net_salary',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'payroll_month' => 'date',
        'payment_date' => 'date',
        'generated_date' => 'date',
        'pdf_generated_at' => 'datetime',
        'transfer_amount' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'per_diem' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'social_insurance' => 'decimal:2',
        'ghs' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function import(): BelongsTo { return $this->belongsTo(PayrollImport::class, 'payroll_import_id'); }

    /**
     * Per-diem rule: 50% of transfer, capped at 1,800 EUR.
     */
    public static function calcPerDiem(float $transfer): float
    {
        return (float) min($transfer * 0.5, 1800.0);
    }

    public static function calcSalary(float $transfer): float
    {
        return max($transfer - self::calcPerDiem($transfer), 0);
    }

    protected static function booted(): void
    {
        static::deleting(function (Payslip $payslip) {
            if ($payslip->pdf_path && Storage::disk('local')->exists($payslip->pdf_path)) {
                Storage::disk('local')->delete($payslip->pdf_path);
            }
        });
    }
}
