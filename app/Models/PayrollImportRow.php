<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollImportRow extends Model
{
    protected $fillable = [
        'payroll_import_id',
        'row_index',
        'date',
        'value_date',
        'description',
        'debit',
        'credit',
        'balance',
        'parsed_name',
        'reference',
        'is_payroll',
        'looks_like_payroll',
        'matched_person_id',
    ];

    protected $casts = [
        'date' => 'date',
        'value_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_payroll' => 'boolean',
        'looks_like_payroll' => 'boolean',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(PayrollImport::class, 'payroll_import_id');
    }

    public function matchedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'matched_person_id');
    }

    /**
     * Per-diem (50% of transfer, capped at 1800 EUR).
     */
    public function perDiem(): float
    {
        return (float) min($this->debit * 0.5, 1800.0);
    }

    /**
     * Gross salary = transfer - per_diem.
     */
    public function grossSalary(): float
    {
        return (float) $this->debit - $this->perDiem();
    }
}
