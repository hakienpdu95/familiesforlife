<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::post('users/bulk-deactivate', [UserController::class, 'bulkDeactivate'])->name('users.bulk-deactivate');
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('users/{user}/password-reset-link', [UserController::class, 'sendPasswordResetLink'])->name('users.password-reset-link');
    Route::resource('users', UserController::class)->except(['show']);
});

Route::permanentRedirect('dashboard/platform-users', '/dashboard/users');

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('users', [UserController::class, 'data'])->name('users');
});
