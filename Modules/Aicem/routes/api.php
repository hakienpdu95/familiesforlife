<?php

use Illuminate\Support\Facades\Route;

// Endpoint status polling cho generation run (spec mục 9.4) — thêm ở Phase 3.
Route::middleware(['auth', 'tenant'])->prefix('aicem')->name('aicem.')->group(function (): void {
    //
});
