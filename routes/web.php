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

// Impersonation Routes (Admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('impersonate/{user}', function (\App\Models\User $user) {
        if (!auth()->user()->canImpersonate() || !$user->canBeImpersonated()) {
            abort(403, 'Cannot impersonate this user.');
        }
        auth()->user()->impersonate($user);
        return redirect()->route('dashboard')->with('success', "Now impersonating {$user->name}");
    })->name('impersonate');

    Route::get('admin/impersonate', function () {
        $users = \App\Models\User::where('is_admin', false)->active()->get();
        return view('admin.users', compact('users'));
    })->name('admin.impersonate');
});

Route::post('leave-impersonation', function () {
    if (auth()->user()->isImpersonated()) {
        auth()->user()->leaveImpersonation();
        return redirect()->route('dashboard')->with('success', 'Stopped impersonating user');
    }
    return redirect()->route('dashboard');
})->middleware(['auth'])->name('leave-impersonation');

Route::middleware(['auth', 'api.credentials'])->group(function () {
    // Driver Management Routes
    Route::resource('drivers', DriverController::class);

    // Declaration Management Routes
    Route::resource('declarations', DeclarationController::class);
    Route::post('declarations/{declaration}/submit', [DeclarationController::class, 'submit'])->name('declarations.submit');
    Route::get('declarations/{declaration}/edit-submitted', [DeclarationController::class, 'editSubmitted'])->name('declarations.edit-submitted');
    Route::put('declarations/{declaration}/update-submitted', [DeclarationController::class, 'updateSubmitted'])->name('declarations.update-submitted');
    Route::post('declarations/{declaration}/withdraw', [DeclarationController::class, 'withdraw'])->name('declarations.withdraw');
    Route::post('declarations/{declaration}/print', [DeclarationController::class, 'print'])->name('declarations.print');

    Route::post('declarations/{declaration}/email', [DeclarationController::class, 'email'])->name('declarations.email');
    Route::get('declarations/driver/{driver}/truck-plates', [DeclarationController::class, 'getDriverTruckPlates'])->name('declarations.driver-truck-plates');

    // Truck Management Routes
    Route::resource('trucks', TruckController::class);
    Route::get('trucks-import', [TruckController::class, 'import'])->name('trucks.import');
    Route::post('trucks-import', [TruckController::class, 'processImport'])->name('trucks.process-import');
    Route::post('trucks/{truck}/assign-driver', [TruckController::class, 'assignDriver'])->name('trucks.assign-driver');
    Route::delete('truck-assignments/{assignment}', [TruckController::class, 'unassignDriver'])->name('trucks.unassign-driver');
});

// Bulk Declaration Update Routes - Using different path to avoid API interception
Route::middleware(['auth'])->prefix('bulk-update')->name('declarations.bulk-update.')->group(function () {
    Route::get('/', [\App\Http\Controllers\BulkDeclarationController::class, 'index'])->name('index');
    Route::post('/step2', [\App\Http\Controllers\BulkDeclarationController::class, 'step2'])->name('step2');
    Route::post('/step3', [\App\Http\Controllers\BulkDeclarationController::class, 'step3'])->name('step3');
    Route::post('/step4', [\App\Http\Controllers\BulkDeclarationController::class, 'step4'])->name('step4');
    Route::post('/step5', [\App\Http\Controllers\BulkDeclarationController::class, 'step5'])->name('step5');
    Route::post('/execute', [\App\Http\Controllers\BulkDeclarationController::class, 'execute'])->name('execute');
    Route::post('/process-declaration', [\App\Http\Controllers\BulkDeclarationController::class, 'processDeclaration'])->name('process-declaration');
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
