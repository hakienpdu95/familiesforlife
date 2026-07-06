<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadSource\Http\Controllers\LeadSourceController;

Route::middleware(['auth', 'verified'])
    ->prefix('leads/admin/sources')
    ->name('lead-source.')
    ->group(function () {
        Route::get('/',               [LeadSourceController::class, 'index'])->name('index');
        Route::get('/create',         [LeadSourceController::class, 'create'])->name('create');
        Route::post('/',              [LeadSourceController::class, 'store'])->name('store');
        Route::get('/{source}/edit',  [LeadSourceController::class, 'edit'])->name('edit');
        Route::put('/{source}',       [LeadSourceController::class, 'update'])->name('update');
        Route::delete('/{source}',    [LeadSourceController::class, 'destroy'])->name('destroy');
        Route::patch('/{source}/toggle', [LeadSourceController::class, 'toggle'])->name('toggle');
    });
