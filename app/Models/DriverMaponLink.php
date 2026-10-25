<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverMaponLink extends Model
{
    protected $fillable = [
        'user_id',
        'imi_driver_id',
        'mapon_driver_id',
        'mapon_name',
    ];
}
