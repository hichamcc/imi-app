<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeclarationController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\Settings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('contact-admin', function () {
    return view('contact-admin');
})->middleware(['auth'])->name('contact-admin');

Route::middleware(['auth', 'api.credentials'])->group(function () {
    // Driver Management Routes
    Route::resource('drivers', DriverController::class);

    // Declaration Management Routes
    Route::resource('declarations', DeclarationController::class);
    Route::post('declarations/{declaration}/submit', [DeclarationController::class, 'submit'])->name('declarations.submit');
    Route::post('declarations/{declaration}/withdraw', [DeclarationController::class, 'withdraw'])->name('declarations.withdraw');
    Route::post('declarations/{declaration}/print', [DeclarationController::class, 'print'])->name('declarations.print');
    Route::post('declarations/{declaration}/email', [DeclarationController::class, 'email'])->name('declarations.email');
    Route::get('declarations/driver/{driver}/truck-plates', [DeclarationController::class, 'getDriverTruckPlates'])->name('declarations.driver-truck-plates');

    // Truck Management Routes
    Route::resource('trucks', TruckController::class);
    Route::post('trucks/{truck}/assign-driver', [TruckController::class, 'assignDriver'])->name('trucks.assign-driver');
    Route::delete('truck-assignments/{assignment}', [TruckController::class, 'unassignDriver'])->name('trucks.unassign-driver');
});

Route::middleware(['auth'])->group(function () {
    // Admin Routes - Only accessible by admins
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::post('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
    });

    // Settings Routes
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [Settings\ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::put('settings/profile', [Settings\ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/profile', [Settings\ProfileController::class, 'destroy'])->name('settings.profile.destroy');
    Route::get('settings/password', [Settings\PasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('settings/password', [Settings\PasswordController::class, 'update'])->name('settings.password.update');
    Route::get('settings/appearance', [Settings\AppearanceController::class, 'edit'])->name('settings.appearance.edit');
});

require __DIR__.'/auth.php';
