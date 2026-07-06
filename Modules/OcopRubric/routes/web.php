<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\OcopRubric\Features\ProductGroupCatalog\Http\ProductGroupController;
use Modules\OcopRubric\Features\ProductRegistry\Http\ProductController;
use Modules\OcopRubric\Features\RubricAuthoring\Http\RubricAuthoringController;
use Modules\OcopRubric\Features\PracticeDeck\Http\PracticeDeckController;
use Modules\OcopRubric\Features\ScoringSession\Http\ScoringSessionController;

// ── System Admin: danh mục 26 Bộ sản phẩm (Phụ lục I) + cây tiêu chí ───────
Route::middleware(['web', 'auth', 'can:' . P::OCOP_RUBRIC_MANAGE->value])
    ->prefix('dashboard/ocop-rubric/admin')
    ->name('ocop_rubric.admin.')
    ->group(function (): void {
        Route::resource('product-groups', ProductGroupController::class)->except(['show']);

        Route::prefix('product-groups/{productGroup}/versions')
            ->name('product-groups.versions.')
            ->group(function (): void {
                Route::get('{version}/tree', [RubricAuthoringController::class, 'tree'])->name('tree');
                Route::get('{version}/validate', [RubricAuthoringController::class, 'validateIntegrity'])->name('validate');
                Route::post('{version}/publish', [RubricAuthoringController::class, 'publish'])->name('publish');
            });

        Route::post('criteria', [RubricAuthoringController::class, 'storeCriterion'])->name('criteria.store');
        Route::put('criteria/{criterion}', [RubricAuthoringController::class, 'updateCriterion'])->name('criteria.update');
        Route::delete('criteria/{criterion}', [RubricAuthoringController::class, 'destroyCriterion'])->name('criteria.destroy');
        Route::post('criteria/reorder', [RubricAuthoringController::class, 'reorderCriteria'])->name('criteria.reorder');
        Route::post('criteria/{criterion}/move/{direction}', [RubricAuthoringController::class, 'moveCriterion'])->name('criteria.move');

        Route::post('options', [RubricAuthoringController::class, 'storeOption'])->name('options.store');
        Route::put('options/{option}', [RubricAuthoringController::class, 'updateOption'])->name('options.update');
        Route::delete('options/{option}', [RubricAuthoringController::class, 'destroyOption'])->name('options.destroy');
    });

// ── Tổ chức: sản phẩm OCOP của tổ chức mình ────────────────────────────────
Route::middleware(['web', 'auth', 'tenant', 'can:' . P::OCOP_PRODUCT_VIEW->value])
    ->prefix('dashboard/ocop')
    ->name('ocop.')
    ->group(function (): void {
        Route::resource('products', ProductController::class);
        Route::post('products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');

        Route::middleware('can:' . P::OCOP_PRACTICE_USE->value)->group(function (): void {
            Route::get('practice/start', [PracticeDeckController::class, 'start'])->name('practice.start');
            Route::post('practice', [ScoringSessionController::class, 'store'])->name('practice.create');
            Route::get('practice/{session}/deck', [ScoringSessionController::class, 'deck'])->name('practice.deck');
            Route::post('practice/{session}/answer', [ScoringSessionController::class, 'answer'])->name('practice.answer');
            Route::post('practice/{session}/skip', [ScoringSessionController::class, 'skip'])->name('practice.skip');
            Route::post('practice/{session}/flag-disqualifier', [ScoringSessionController::class, 'flagDisqualifier'])->name('practice.flag');
            Route::post('practice/{session}/complete', [ScoringSessionController::class, 'complete'])->name('practice.complete');
            Route::get('practice/{session}/summary', [ScoringSessionController::class, 'summary'])->name('practice.summary');
            Route::post('practice/{session}/abandon', [ScoringSessionController::class, 'abandon'])->name('practice.abandon');
            Route::post('practice/{session}/duplicate', [ScoringSessionController::class, 'duplicate'])->name('practice.duplicate');
            Route::get('practice/history', [PracticeDeckController::class, 'history'])->name('practice.history');
        });

        Route::middleware('can:' . P::OCOP_SELF_ASSESS_USE->value)->group(function (): void {
            Route::post('products/{product}/self-assessment', [ScoringSessionController::class, 'startSelfAssessment'])
                ->name('self_assessment.start');
        });
    });
