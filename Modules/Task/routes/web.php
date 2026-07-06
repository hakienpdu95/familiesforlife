<?php

use Illuminate\Support\Facades\Route;
use Modules\Task\Http\Controllers\Api\TaskApiController;
use Modules\Task\Http\Controllers\TaskCommentController;
use Modules\Task\Http\Controllers\TaskController;
use Modules\Task\Http\Controllers\TaskWatcherController;

/*
|--------------------------------------------------------------------------
| Task Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('tasks', TaskController::class);

    // Comments (JSON responses — called via fetch from show page)
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])
        ->name('tasks.comments.store');
    Route::put('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])
        ->name('tasks.comments.update');
    Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])
        ->name('tasks.comments.destroy');

    // Watcher toggle
    Route::post('tasks/{task}/watch', [TaskWatcherController::class, 'toggle'])
        ->name('tasks.watch');
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('tasks', [TaskApiController::class, 'index'])->name('tasks');
    Route::get('projects/{project}/tasks', [TaskApiController::class, 'byProject'])->name('projects.tasks');
});
