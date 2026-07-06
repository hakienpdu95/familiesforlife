<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleScope\Http\Controllers\RoleScopeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('rolescopes', RoleScopeController::class)->names('rolescope');
});
