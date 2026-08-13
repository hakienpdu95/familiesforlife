<?php

use Illuminate\Support\Facades\Route;
use Modules\EntityComparison\Features\Comparison\Http\EntityComparisonController;

/*
|--------------------------------------------------------------------------
| EntityComparison Module — API Routes  (prefix: /api)
|--------------------------------------------------------------------------
*/

// spec/Entity_Comparison_Module_Technical_Spec.md §8 — public compare API cho AJAX re-render,
// KHÔNG cần đăng nhập (§0 mục 9). entity_type_id nằm trong body, không trong path.
Route::post('entity-comparison/compare', [EntityComparisonController::class, 'compareApi'])
    ->name('entity_comparison.public.compare-api');
