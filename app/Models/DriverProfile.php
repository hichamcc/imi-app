<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverProfile extends Model
{
    protected $fillable = [
        'driver_id',
        'email',
        'auto_renew',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
    ];

    /**
     * Get or create a driver profile
     */
    public static function getOrCreateForDriver($driverId)
    {
        return static::firstOrCreate(
            ['driver_id' => $driverId],
            [
                'email' => null,
                'auto_renew' => true
            ]
        );
    }

    /**
     * Update driver profile email
     */
    public function updateEmail($email)
    {
        $this->update(['email' => $email]);
        return $this;
    }

    /**
     * Get driver email by driver ID
     */
    public static function getDriverEmail($driverId)
    {
        $profile = static::where('driver_id', $driverId)->first();
        return $profile?->email;
    }

    /**
     * Toggle auto renew status for a driver
     */
    public function toggleAutoRenew()
    {
        $this->update(['auto_renew' => !$this->auto_renew]);
        return $this;
    }

    /**
     * Check if driver has auto renew enabled
     */
    public static function isAutoRenewEnabled($driverId)
    {
        $profile = static::where('driver_id', $driverId)->first();
        return $profile ? $profile->auto_renew : true; // Default true if no profile exists
    }
}