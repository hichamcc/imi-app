<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverProfile extends Model
{
    protected $fillable = [
        'driver_id',
        'email',
    ];

    /**
     * Get or create a driver profile
     */
    public static function getOrCreateForDriver($driverId)
    {
        return static::firstOrCreate(
            ['driver_id' => $driverId],
            ['email' => null]
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
}