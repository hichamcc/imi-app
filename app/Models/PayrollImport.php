<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PayrollImport extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'account_number',
        'currency',
        'payroll_month',
        'is_payroll',
        'status',
        'total_rows',
        'payslips_generated',
        'processed_at',
    ];

    protected $casts = [
        'payroll_month' => 'date',
        'processed_at' => 'datetime',
        'is_payroll' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PayrollImportRow::class)->orderBy('row_index');
    }

    protected static function booted(): void
    {
        static::deleting(function (PayrollImport $import) {
            if ($import->stored_path && Storage::disk('local')->exists($import->stored_path)) {
                Storage::disk('local')->delete($import->stored_path);
            }
        });
    }
}
