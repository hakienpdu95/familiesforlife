<?php

use Illuminate\Support\Facades\Route;
use Modules\Lead\Http\Controllers\Api\LeadActivityApiController;
use Modules\Lead\Http\Controllers\Api\LeadApiController;
use Modules\Lead\Http\Controllers\Api\LeadNoteApiController;
use Modules\Lead\Http\Controllers\Api\LeadTagApiController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1/leads')->name('api.lead.')->group(function () {

    // ── Tabulator listing & kanban ────────────────────────────────────
    Route::get('/listing', [LeadApiController::class, 'listing'])->name('listing');
    Route::get('/kanban',  [LeadApiController::class, 'kanban'])->name('kanban');

    // ── Stats ─────────────────────────────────────────────────────────
    Route::get('/stats',   [LeadApiController::class, 'stats'])->name('stats');

    // ── Config lists (for dropdowns) ──────────────────────────────────
    Route::get('/stages',           [LeadApiController::class, 'stageList'])->name('stages');
    Route::get('/sources',          [LeadApiController::class, 'sourceList'])->name('sources');
    Route::get('/assignable-users', [LeadApiController::class, 'assignableUsers'])->name('assignable-users');
    Route::get('/tags',             [LeadTagApiController::class, 'list'])->name('tags.list');

    // ── Tag sync on a lead ────────────────────────────────────────────
    Route::put('/{lead}/tags', [LeadTagApiController::class, 'sync'])->name('tags.sync');

    // ── Notes ─────────────────────────────────────────────────────────
    Route::post('/{lead}/notes',                   [LeadNoteApiController::class, 'store'])->name('notes.store');
    Route::put('/{lead}/notes/{note}',             [LeadNoteApiController::class, 'update'])->name('notes.update');
    Route::delete('/{lead}/notes/{note}',          [LeadNoteApiController::class, 'destroy'])->name('notes.destroy');
    Route::post('/{lead}/notes/{note}/toggle-pin', [LeadNoteApiController::class, 'togglePin'])->name('notes.toggle-pin');

    // ── Activities ────────────────────────────────────────────────────
    Route::post('/{lead}/activities', [LeadActivityApiController::class, 'store'])->name('activities.store');
});
