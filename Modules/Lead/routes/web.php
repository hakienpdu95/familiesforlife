<?php

use Illuminate\Support\Facades\Route;
use Modules\Lead\Http\Controllers\LeadController;
use Modules\Lead\Http\Controllers\LeadTagController;

Route::middleware(['auth', 'verified', 'feature:module.lead'])->prefix('leads')->name('lead.')->group(function () {

    // ── Core CRUD ────────────────────────────────────────────────────
    Route::get('/',            [LeadController::class, 'index'])->name('index');
    Route::get('/create',      [LeadController::class, 'create'])->name('create');
    Route::post('/',           [LeadController::class, 'store'])->name('store');
    Route::get('/{lead}',      [LeadController::class, 'show'])->name('show');
    Route::get('/{lead}/edit', [LeadController::class, 'edit'])->name('edit');
    Route::put('/{lead}',      [LeadController::class, 'update'])->name('update');
    Route::delete('/{lead}',   [LeadController::class, 'destroy'])->name('destroy');

    // ── AJAX actions ─────────────────────────────────────────────────
    Route::post('/{lead}/change-stage', [LeadController::class, 'changeStage'])->name('change-stage');
    Route::post('/{lead}/assign',       [LeadController::class, 'assign'])->name('assign');

    // ── Export ────────────────────────────────────────────────────────
    Route::get('/export/excel', [LeadController::class, 'export'])->name('export');

    // ── Tag definitions (admin) ────────────────────────────────────────
    Route::prefix('admin/tags')->name('tags.')->group(function () {
        Route::get('/',              [LeadTagController::class, 'index'])->name('index');
        Route::post('/',             [LeadTagController::class, 'store'])->name('store');
        Route::get('/{tag}/edit',    [LeadTagController::class, 'edit'])->name('edit');
        Route::put('/{tag}',         [LeadTagController::class, 'update'])->name('update');
        Route::delete('/{tag}',      [LeadTagController::class, 'destroy'])->name('destroy');
    });
});
