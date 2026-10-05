<?php

use App\Http\Controllers\Api\TruckDeclarationsController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /api/external (see bootstrap/app.php).

Route::middleware('external.token')->group(function () {
    // Look up a driver's active IMI declarations by truck plate + driver name.
    // Called by wttsystem.dk after it resolves the truck → driver via MAPON.
    Route::get('truck-declarations', [TruckDeclarationsController::class, 'lookup']);
});
