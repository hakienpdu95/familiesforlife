<?php

use Illuminate\Support\Facades\Route;
use Modules\Assessment\Http\Controllers\AssessmentController;
use Modules\Assessment\Http\Controllers\AssessmentConfigController;
use Modules\Assessment\Http\Controllers\AssessmentPublicResultController;
use Modules\Assessment\Http\Controllers\AssessmentResultController;
use Modules\Assessment\Http\Middleware\ValidateAssessmentResultToken;

Route::middleware(['auth', 'verified', 'feature:module.assessment'])->prefix('dashboard/assessments')->name('assessments.')->group(function () {

    Route::get('/',                    [AssessmentController::class, 'index'])->name('index');
    Route::get('/create',              [AssessmentController::class, 'create'])->name('create');
    Route::post('/',                   [AssessmentController::class, 'store'])->name('store');
    Route::get('/{assessment}',        [AssessmentController::class, 'show'])->name('show');
    Route::get('/{assessment}/edit',   [AssessmentController::class, 'edit'])->name('edit');
    Route::put('/{assessment}',        [AssessmentController::class, 'update'])->name('update');
    Route::delete('/{assessment}',     [AssessmentController::class, 'destroy'])->name('destroy');

    Route::prefix('/{assessment}/config')->name('config.')->group(function () {
        Route::get('/',               [AssessmentConfigController::class, 'index'])->name('index');
        Route::get('/data',           [AssessmentConfigController::class, 'getConfig'])->name('get');
        Route::post('/',              [AssessmentConfigController::class, 'saveConfig'])->name('save');
        Route::post('/validate',      [AssessmentConfigController::class, 'validateConfig'])->name('validate');
        Route::get('/snapshots',      [AssessmentConfigController::class, 'snapshots'])->name('snapshots');
        Route::post('/rollback',      [AssessmentConfigController::class, 'rollback'])->name('rollback');
        Route::post('/reprocess',     [AssessmentConfigController::class, 'reprocessAll'])->name('reprocess');
        Route::get('/fields',         [AssessmentConfigController::class, 'getFields'])->name('fields');
        Route::get('/flags',          [AssessmentConfigController::class, 'getFlags'])->name('flags');
        Route::get('/batch/{batchId}',[AssessmentConfigController::class, 'getBatchStatus'])->name('batch-status');
    });

    Route::prefix('/{assessment}/results')->name('results.')->group(function () {
        Route::get('/',                      [AssessmentResultController::class, 'index'])->name('index');
        Route::get('/{result}',              [AssessmentResultController::class, 'show'])->name('show');
        Route::post('/{result}/recalculate', [AssessmentResultController::class, 'recalculate'])->name('recalculate');
        Route::patch('/{result}/feedback',   [AssessmentResultController::class, 'feedback'])->name('feedback');
    });
});

// Public result page — không cần auth, validate bằng token
Route::get('/assessment-result/{token}', [AssessmentPublicResultController::class, 'show'])
     ->name('assessment.result.public')
     ->middleware(ValidateAssessmentResultToken::class);
