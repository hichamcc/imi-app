<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeclarationController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverProfileController;
use App\Http\Controllers\PayrollImportController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\UserGroupController;
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

// Impersonation Routes (Auth users with group permissions)
Route::middleware(['auth'])->group(function () {
    Route::post('impersonate/{user}', function (\App\Models\User $user, \Illuminate\Http\Request $request) {
        if (!auth()->user()->canImpersonateUser($user)) {
            abort(403, 'Cannot impersonate this user.');
        }

        // Clear cache before impersonation to ensure fresh API data
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        auth()->user()->impersonate($user);

        // Check if redirect parameter is provided
        $redirectTo = $request->input('redirect');
        if ($redirectTo && filter_var($redirectTo, FILTER_VALIDATE_URL) === false) {
            // If redirect is a relative path (not a full URL), use it
            return redirect($redirectTo)->with('success', "Now impersonating {$user->name}");
        }

        return redirect()->route('dashboard')->with('success', "Now impersonating {$user->name}");
    })->name('impersonate');

    Route::get('admin/impersonate', function () {
        $users = \App\Models\User::where('is_admin', false)->active()->get();
        return view('admin.users', compact('users'));
    })->name('admin.impersonate');
});

Route::post('leave-impersonation', function () {
    if (auth()->user()->isImpersonated()) {
        // Clear cache before leaving impersonation to ensure fresh API data
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        auth()->user()->leaveImpersonation();
        return redirect()->route('dashboard')->with('success', 'Stopped impersonating user');
    }
    return redirect()->route('dashboard');
})->middleware(['auth'])->name('leave-impersonation');

Route::middleware(['auth'])->group(function () {
    // API endpoint to get impersonatable users
    Route::get('api/impersonatable-users', function () {
        $users = auth()->user()->getImpersonatableUsers();
        return response()->json([
            'success' => true,
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            })
        ]);
    });
});

Route::middleware(['auth', 'api.credentials'])->group(function () {
    // Driver Management Routes
    Route::post('drivers/bulk-clone', [DriverController::class, 'bulkClone'])->name('drivers.bulk-clone');
    Route::resource('drivers', DriverController::class);
    Route::get('drivers/{driver}/declarations', [DriverController::class, 'getDeclarations'])->name('drivers.declarations');
    Route::post('drivers/send-declarations', [DriverController::class, 'sendDeclarations'])->name('drivers.send-declarations');
    Route::post('drivers/{driver}/clone', [DriverController::class, 'clone'])->name('drivers.clone');
    Route::get('drivers/{driver}/download-certificate', [DriverController::class, 'downloadCertificate'])->name('drivers.download-certificate');

    // Declaration Management Routes
    Route::resource('declarations', DeclarationController::class);
    Route::post('declarations/{declaration}/submit', [DeclarationController::class, 'submit'])->name('declarations.submit');
    Route::get('declarations/{declaration}/edit-submitted', [DeclarationController::class, 'editSubmitted'])->name('declarations.edit-submitted');
    Route::put('declarations/{declaration}/update-submitted', [DeclarationController::class, 'updateSubmitted'])->name('declarations.update-submitted');
    Route::post('declarations/{declaration}/withdraw', [DeclarationController::class, 'withdraw'])->name('declarations.withdraw');
    Route::post('declarations/{declaration}/print', [DeclarationController::class, 'print'])->name('declarations.print');
    Route::post('declarations/bulk-delete', [DeclarationController::class, 'bulkDelete'])->name('declarations.bulk-delete');
    Route::post('declarations/bulk-withdraw', [DeclarationController::class, 'bulkWithdraw'])->name('declarations.bulk-withdraw');

    Route::post('declarations/{declaration}/email', [DeclarationController::class, 'email'])->name('declarations.email');
    Route::get('declarations/driver/{driver}/truck-plates', [DeclarationController::class, 'getDriverTruckPlates'])->name('declarations.driver-truck-plates');

    // Truck Management Routes
    Route::get('trucks/export-template', [TruckController::class, 'exportTemplate'])->name('trucks.export-template');
    Route::post('trucks/bulk-update-vehicle-fields', [TruckController::class, 'bulkUpdateVehicleFields'])->name('trucks.bulk-update-vehicle-fields');
    Route::resource('trucks', TruckController::class);
    Route::get('trucks-import', [TruckController::class, 'import'])->name('trucks.import');
    Route::post('trucks-import', [TruckController::class, 'processImport'])->name('trucks.process-import');
    Route::post('trucks/{truck}/assign-driver', [TruckController::class, 'assignDriver'])->name('trucks.assign-driver');
    Route::delete('truck-assignments/{assignment}', [TruckController::class, 'unassignDriver'])->name('trucks.unassign-driver');

    // HR — Person Routes
    Route::post('persons/{person}/files', [PersonController::class, 'uploadFile'])->name('persons.files.upload');
    Route::get('persons/{person}/files/{file}/download', [PersonController::class, 'downloadFile'])->name('persons.files.download');
    Route::delete('persons/{person}/files/{file}', [PersonController::class, 'deleteFile'])->name('persons.files.destroy');
    Route::post('persons/{person}/sync-to-imi', [PersonController::class, 'syncToImi'])->name('persons.sync-to-imi');
    Route::post('persons/{person}/link-to-imi', [PersonController::class, 'linkToImiDriver'])->name('persons.link-to-imi');
    Route::get('persons/{person}/contract.pdf', [PersonController::class, 'generateContract'])->name('persons.contract');

    // Payroll imports
    Route::get('payroll-imports', [PayrollImportController::class, 'index'])->name('payroll-imports.index');
    Route::get('payroll-imports/create', [PayrollImportController::class, 'create'])->name('payroll-imports.create');
    Route::post('payroll-imports', [PayrollImportController::class, 'store'])->name('payroll-imports.store');
    Route::get('payroll-imports/{payrollImport}/review', [PayrollImportController::class, 'review'])->name('payroll-imports.review');
    Route::put('payroll-imports/{payrollImport}/review', [PayrollImportController::class, 'updateReview'])->name('payroll-imports.review.update');
    Route::delete('payroll-imports/{payrollImport}', [PayrollImportController::class, 'destroy'])->name('payroll-imports.destroy');
    Route::post('payroll-imports/{payrollImport}/rows/{row}/create-person', [PayrollImportController::class, 'createPersonFromRow'])->name('payroll-imports.rows.create-person');
    Route::post('payroll-imports/{payrollImport}/generate-payslips', [PayrollImportController::class, 'generatePayslips'])->name('payroll-imports.generate-payslips');

    // Payslips
    Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('payslips/{payslip}/view', [PayslipController::class, 'view'])->name('payslips.view');
    Route::get('payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payslips.download');
    Route::post('payslips/{payslip}/regenerate', [PayslipController::class, 'regenerate'])->name('payslips.regenerate');
    Route::delete('payslips/{payslip}', [PayslipController::class, 'destroy'])->name('payslips.destroy');
    Route::post('persons-refresh-imi-presence', [PersonController::class, 'refreshImiPresence'])->name('persons.refresh-imi-presence');
    Route::get('persons-import-from-imi', [PersonController::class, 'importFromImiIndex'])->name('persons.import-from-imi');
    Route::post('persons-import-from-imi', [PersonController::class, 'importFromImiOne'])->name('persons.import-from-imi.one');
    Route::post('persons-import-from-imi/bulk', [PersonController::class, 'importFromImiBulk'])->name('persons.import-from-imi.bulk');
    Route::resource('persons', PersonController::class);

    // Driver Profile Routes
    Route::post('driver-profiles/update-email', [DriverProfileController::class, 'updateEmail'])->name('driver-profiles.update-email');
    Route::post('driver-profiles/bulk-update-emails', [DriverProfileController::class, 'bulkUpdateEmails'])->name('driver-profiles.bulk-update-emails');
    Route::post('driver-profiles/toggle-auto-renew', [DriverProfileController::class, 'toggleAutoRenew'])->name('driver-profiles.toggle-auto-renew');
    Route::get('driver-profiles/{driverId}', [DriverProfileController::class, 'show'])->name('driver-profiles.show');
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
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::post('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        // Group Management Routes
        Route::post('groups', [UserGroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group}', [UserGroupController::class, 'show'])->name('groups.show');
        Route::put('groups/{group}', [UserGroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [UserGroupController::class, 'destroy'])->name('groups.destroy');
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
