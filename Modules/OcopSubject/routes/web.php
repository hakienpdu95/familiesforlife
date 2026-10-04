<?php

use Illuminate\Support\Facades\Route;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\OcopSubjectAdminController;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\OcopSubjectApiController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function (): void {
    Route::resource('ocop-subjects', OcopSubjectAdminController::class)->except(['show']);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function (): void {
    Route::get('ocop-subjects', [OcopSubjectApiController::class, 'index'])->name('ocop-subjects');
});
