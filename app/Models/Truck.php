<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Truck extends Model
{
    protected $fillable = [
        'name',
        'plate',
        'capacity_tons',
        'status',
    ];

    protected $casts = [
        'capacity_tons' => 'decimal:2',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(TruckAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(TruckAssignment::class)->where('is_active', true);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Available' => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900',
            'In-Transit' => 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900',
            'Maintenance' => 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-900',
            'Retired' => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-900',
            default => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-900',
        };
    }

    public function canBeAssigned(): bool
    {
        return in_array($this->status, ['Available', 'In-Transit']);
    }

    public static function getStatuses(): array
    {
        return [
            'Available' => 'Available',
            'In-Transit' => 'In-Transit',
            'Maintenance' => 'Maintenance',
            'Retired' => 'Retired',
        ];
    }
}
