<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\SolutionCatalog\Http\Controllers\SolutionActivationController;
use Modules\SolutionCatalog\Http\Controllers\SolutionCatalogController;

Route::middleware(['web', 'auth', 'tenant', 'can:' . P::SOLUTION_CATALOG_VIEW->value])
    ->prefix('dashboard/solution-catalog')
    ->name('solution_catalog.')
    ->group(function (): void {
        Route::get('/', [SolutionCatalogController::class, 'index'])->name('index');
        Route::get('{businessSolution:slug}', [SolutionCatalogController::class, 'show'])->name('show');
        Route::post('{businessSolution:slug}/activate', [SolutionActivationController::class, 'activate'])
            ->middleware('can:' . P::SOLUTION_ACTIVATE->value)
            ->name('activate');
    });
