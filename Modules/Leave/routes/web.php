<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\Http\Controllers\LeaveBalanceController;
use Modules\Leave\Http\Controllers\LeavePolicyController;
use Modules\Leave\Http\Controllers\LeaveRequestController;

/*
|--------------------------------------------------------------------------
| Leave Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    // ── Leave Policies ─────────────────────────────────────────────────────────
    Route::prefix('leave/policies')->name('leave.policies.')->group(function () {
        Route::get('/',          [LeavePolicyController::class, 'index'])->name('index');
        Route::get('/create',    [LeavePolicyController::class, 'create'])->name('create');
        Route::post('/',         [LeavePolicyController::class, 'store'])->name('store');
        Route::get('/{policy}/edit', [LeavePolicyController::class, 'edit'])->name('edit');
        Route::put('/{policy}',  [LeavePolicyController::class, 'update'])->name('update');
        Route::delete('/{policy}', [LeavePolicyController::class, 'destroy'])->name('destroy');
    });

    // ── Leave Requests ─────────────────────────────────────────────────────────
    Route::prefix('leave/requests')->name('leave.requests.')->group(function () {
        Route::get('/',               [LeaveRequestController::class, 'index'])->name('index');
        Route::get('/create',         [LeaveRequestController::class, 'create'])->name('create');
        Route::post('/',              [LeaveRequestController::class, 'store'])->name('store');
        Route::get('/pending',        [LeaveRequestController::class, 'pending'])->name('pending');
        Route::get('/{request}',      [LeaveRequestController::class, 'show'])->name('show');
        Route::post('/{request}/approve', [LeaveRequestController::class, 'approve'])->name('approve');
        Route::post('/{request}/reject',  [LeaveRequestController::class, 'reject'])->name('reject');
        Route::post('/{request}/cancel',  [LeaveRequestController::class, 'cancel'])->name('cancel');
    });

    // ── Leave Balances ─────────────────────────────────────────────────────────
    Route::prefix('leave/balances')->name('leave.balances.')->group(function () {
        Route::get('/me', [LeaveBalanceController::class, 'me'])->name('me');
        Route::get('/employee/{employee}', [LeaveBalanceController::class, 'forEmployee'])->name('employee');
    });

    // ── Leave internal API ─────────────────────────────────────────────────────
    Route::get('/leave/api/employees', [LeaveRequestController::class, 'apiEmployees'])->name('leave.api.employees');

});
