<?php

use Illuminate\Support\Facades\Route;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\OcopSubjectAdminController;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\OcopSubjectApiController;
use Modules\OcopSubject\Features\PublicReading\Http\BrandController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function (): void {
    Route::resource('ocop-subjects', OcopSubjectAdminController::class)->except(['show']);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function (): void {
    Route::get('ocop-subjects', [OcopSubjectApiController::class, 'index'])->name('ocop-subjects');
});

Route::get('brands/{slug}', [BrandController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('ocop-subject.public.show');
