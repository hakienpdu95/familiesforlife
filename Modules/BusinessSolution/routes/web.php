<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Http\Controllers\BusinessSolutionController;

Route::middleware(['web', 'auth', 'can:' . P::SOLUTION_CATALOG_MANAGE->value])
    ->prefix('dashboard/business-solutions/admin')
    ->name('business_solutions.admin.')
    ->group(function (): void {
        Route::get('/', [BusinessSolutionController::class, 'index'])->name('index');
        Route::get('create', [BusinessSolutionController::class, 'create'])->name('create');
        Route::post('/', [BusinessSolutionController::class, 'store'])->name('store');
        Route::get('{businessSolution}/edit', [BusinessSolutionController::class, 'edit'])->name('edit');
        Route::put('{businessSolution}', [BusinessSolutionController::class, 'update'])->name('update');
        Route::delete('{businessSolution}', [BusinessSolutionController::class, 'destroy'])->name('destroy');
        Route::post('{businessSolution}/publish', [BusinessSolutionController::class, 'publish'])->name('publish');
        Route::post('{businessSolution}/archive', [BusinessSolutionController::class, 'archive'])->name('archive');
    });
