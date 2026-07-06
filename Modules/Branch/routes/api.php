<?php

use Illuminate\Support\Facades\Route;
use Modules\Branch\Http\Controllers\BranchController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    Route::get('branches/options', [BranchController::class, 'options'])->name('branches.options');
    Route::apiResource('branches', BranchController::class)->names('branch');
});
