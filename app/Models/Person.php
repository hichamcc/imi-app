<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $table = 'persons';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'document_type',
        'document_number',
        'document_issuing_country',
        'license_number',
        'address_street',
        'address_post_code',
        'address_city',
        'address_country',
        'contract_start_date',
        'applicable_law',
        'position',
        'email',
        'phone',
        'bank_iban',
        'bank_swift',
        'monthly_salary',
        'imi_driver_id',
        'imi_user_id',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'contract_start_date' => 'date',
        'monthly_salary' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imiUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imi_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PersonFile::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }
}
