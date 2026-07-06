<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\EmployeeAvatarController;
use Modules\Employee\Http\Controllers\Api\EmployeeOptionsController;
use Modules\Employee\Http\Controllers\EmployeeController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    Route::get('employees/options', EmployeeOptionsController::class)->name('employees.options');
    Route::apiResource('employees', EmployeeController::class)->names('employee');

    // Avatar upload / delete
    Route::post('employees/{employee}/avatar',   [EmployeeAvatarController::class, 'store'])->name('employees.avatar.store');
    Route::delete('employees/{employee}/avatar', [EmployeeAvatarController::class, 'destroy'])->name('employees.avatar.destroy');
});
