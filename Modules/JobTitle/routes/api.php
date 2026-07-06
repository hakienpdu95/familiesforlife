<?php

use Illuminate\Support\Facades\Route;
use Modules\JobTitle\Http\Controllers\JobTitleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('jobtitles/options', [JobTitleController::class, 'options'])->name('jobtitle.options');
    Route::apiResource('jobtitles', JobTitleController::class)->names('jobtitle');
});
