<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckAssignment extends Model
{
    protected $fillable = [
        'truck_id',
        'driver_id',
        'assigned_date',
        'unassigned_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'unassigned_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDriver($query, string $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeForTruck($query, int $truckId)
    {
        return $query->where('truck_id', $truckId);
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->assigned_date) {
            return null;
        }

        $endDate = $this->unassigned_date ?? now();
        return $this->assigned_date->diffInDays($endDate);
    }
}
