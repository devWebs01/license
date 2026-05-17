<?php

use DevWebs01\LicensingClient\Http\Controllers\LicenseWizardController;
use Illuminate\Support\Facades\Route;

$prefix = config('licensing-client.route_prefix', 'licensing');

Route::prefix($prefix)->group(function () {
    Route::get('/activate', [LicenseWizardController::class, 'showActivate'])->name('licensing.activate');
    Route::post('/activate', [LicenseWizardController::class, 'activate']);
    Route::get('/status', [LicenseWizardController::class, 'showStatus'])->name('licensing.status');
    Route::get('/locked', [LicenseWizardController::class, 'showLocked'])->name('licensing.locked');
});
