<?php

use Illuminate\Support\Facades\Route;
use Modules\Department\Http\Controllers\DepartmentController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    Route::get('departments/options', [DepartmentController::class, 'options'])->name('departments.options');
    Route::apiResource('departments', DepartmentController::class)->names('department');
});
