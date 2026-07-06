<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\Api\CustomerApiController;
use Modules\Customer\Http\Controllers\Api\CustomerActivityApiController;
use Modules\Customer\Http\Controllers\Api\CustomerNoteApiController;

Route::middleware(['auth', 'verified', 'feature:module.customer'])
    ->prefix('customers')
    ->name('customer.')
    ->group(function () {

        // ── Tabulator AJAX ────────────────────────────────────────────
        Route::get('/api/list',   [CustomerApiController::class, 'index'])->name('api.list');
        Route::get('/api/search', [CustomerApiController::class, 'search'])->name('api.search');

        // ── Core CRUD ────────────────────────────────────────────────
        Route::get('/',                 [CustomerController::class, 'index'])->name('index');
        Route::get('/create',           [CustomerController::class, 'create'])->name('create');
        Route::post('/',                [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}',       [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit',  [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}',       [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}',    [CustomerController::class, 'destroy'])->name('destroy');

        // ── Activity AJAX ─────────────────────────────────────────────
        Route::post('/{customer}/activities', [CustomerActivityApiController::class, 'store'])->name('activities.store');

        // ── Note AJAX ─────────────────────────────────────────────────
        Route::post('/{customer}/notes',                   [CustomerNoteApiController::class, 'store'])->name('notes.store');
        Route::put('/{customer}/notes/{note}',             [CustomerNoteApiController::class, 'update'])->name('notes.update');
        Route::delete('/{customer}/notes/{note}',          [CustomerNoteApiController::class, 'destroy'])->name('notes.destroy');
        Route::post('/{customer}/notes/{note}/toggle-pin', [CustomerNoteApiController::class, 'togglePin'])->name('notes.toggle-pin');
    });
