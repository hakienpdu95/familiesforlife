<?php

use Illuminate\Support\Facades\Route;
use Modules\Survey\Http\Controllers\Api\SurveyBackendApiController;
use Modules\Survey\Http\Controllers\FieldController;
use Modules\Survey\Http\Controllers\WebhookController;
use Modules\Survey\Http\Controllers\OptionController;
use Modules\Survey\Http\Controllers\ResponseController;
use Modules\Survey\Http\Controllers\SectionController;
use Modules\Survey\Http\Controllers\SurveyScoringRedirectController;
use Modules\Survey\Http\Controllers\StatsController;
use Modules\Survey\Http\Controllers\SurveyController;
use Modules\Survey\Http\Controllers\SurveyResultController;
use Modules\Survey\Http\Controllers\SurveyTakeController;
use Modules\Survey\Http\Controllers\TokenController;
use Modules\Survey\Http\Controllers\TurnstileSiteController;

/*
|--------------------------------------------------------------------------
| Survey Module — Backend Admin Routes
|--------------------------------------------------------------------------
| Web CRUD:  prefix = dashboard/surveys  → name = backend.surveys.*
| Builder JSON API: trả JSON, dùng bởi Alpine.js fetch() trong builder.
*/

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    // ── Survey CRUD ────────────────────────────────────────────────────
    Route::prefix('surveys')->name('surveys.')->group(function () {
        Route::get('/',                   [SurveyController::class, 'index'])->name('index');
        Route::get('/create',             [SurveyController::class, 'create'])->name('create');
        Route::post('/',                  [SurveyController::class, 'store'])->name('store');
        Route::get('/{survey}/edit',      [SurveyController::class, 'edit'])->name('edit');
        Route::put('/{survey}',           [SurveyController::class, 'update'])->name('update');
        Route::delete('/{survey}',        [SurveyController::class, 'destroy'])->name('destroy');
        Route::post('/{survey}/activate', [SurveyController::class, 'activate'])->name('activate');

        // ── Token management ───────────────────────────────────────────
        Route::prefix('/{survey}/tokens')->name('tokens.')->group(function () {
            Route::get('/',                    [TokenController::class, 'index'])->name('index');
            Route::post('/',                   [TokenController::class, 'store'])->name('store');
            Route::get('/{token}/reveal',      [TokenController::class, 'reveal'])->name('reveal');
            Route::patch('/{token}/revoke',    [TokenController::class, 'revoke'])->name('revoke');
            Route::delete('/{token}',          [TokenController::class, 'destroy'])->name('destroy');
        });

        // ── Stats dashboard ────────────────────────────────────────────
        Route::get('/{survey}/stats', [StatsController::class, 'index'])->name('stats.index');
        Route::get('/{survey}/stats/fields/{field}', [StatsController::class, 'fieldStats'])->name('stats.field');

        // ── Scoring config — redirect sang Assessment module ───────────
        Route::get('/{survey}/scoring', [SurveyScoringRedirectController::class, 'redirect'])
             ->name('scoring.index');

        // ── Scoring results ────────────────────────────────────────────
        Route::prefix('/{survey}/results')->name('results.')->group(function () {
            // T6.3 — Tổng hợp scoring toàn survey
            Route::get('/summary', [SurveyResultController::class, 'summary'])->name('summary');
        });

        // ── Response management + export ───────────────────────────────
        // (responses routes đã có bên dưới, thêm result routes vào đây)

        // ── Response management + export ───────────────────────────────
        Route::prefix('/{survey}/responses')->name('responses.')->group(function () {
            Route::get('/',                         [ResponseController::class, 'index'])->name('index');
            Route::get('/export',                   [ResponseController::class, 'export'])->name('export');
            Route::get('/export/download/{key}',    [ResponseController::class, 'downloadExport'])->name('export.download');
            Route::get('/{response}',               [ResponseController::class, 'show'])->name('show');
            Route::delete('/{response}',            [ResponseController::class, 'destroy'])->name('destroy');

            // T6.2 — Admin xem chi tiết result một response
            Route::get('/{response}/result',        [SurveyResultController::class, 'show'])->name('result.show');
            // T6.4 — Admin force recalculate
            Route::post('/{response}/recalculate',  [SurveyResultController::class, 'recalculate'])->name('result.recalculate');
            // T6 — Admin xác nhận actual_band cho scoring_feedback
            Route::patch('/{response}/feedback',    [SurveyResultController::class, 'submitFeedback'])->name('result.feedback');
        });

        // ── Webhook management ─────────────────────────────────────────
        Route::prefix('/{survey}/webhooks')->name('webhooks.')->group(function () {
            Route::get('/',          [WebhookController::class, 'index'])->name('index');
            Route::post('/',         [WebhookController::class, 'store'])->name('store');
            Route::patch('/{webhook}', [WebhookController::class, 'update'])->name('update');
            Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
        });

        // ── Section builder (JSON) ─────────────────────────────────────
        Route::prefix('/{survey}/sections')->name('sections.')->group(function () {
            Route::post('/',               [SectionController::class, 'store'])->name('store');
            Route::put('/{section}',       [SectionController::class, 'update'])->name('update');
            Route::delete('/{section}',    [SectionController::class, 'destroy'])->name('destroy');
            Route::patch('/reorder',       [SectionController::class, 'reorder'])->name('reorder');
        });

        // ── Field builder (JSON) ───────────────────────────────────────
        Route::prefix('/{survey}/fields')->name('fields.')->group(function () {
            Route::post('/',                [FieldController::class, 'store'])->name('store');
            Route::put('/{field}',          [FieldController::class, 'update'])->name('update');
            Route::delete('/{field}',       [FieldController::class, 'destroy'])->name('destroy');
            Route::patch('/{field}/toggle', [FieldController::class, 'toggleActive'])->name('toggle');
            Route::patch('/reorder',        [FieldController::class, 'reorder'])->name('reorder');

            // ── Option builder (JSON) ──────────────────────────────────
            Route::prefix('/{field}/options')->name('options.')->group(function () {
                Route::post('/',             [OptionController::class, 'store'])->name('store');
                Route::put('/{option}',      [OptionController::class, 'update'])->name('update');
                Route::delete('/{option}',   [OptionController::class, 'destroy'])->name('destroy');
                Route::patch('/reorder',     [OptionController::class, 'reorder'])->name('reorder');
            });
        });
    });

    // ── Turnstile sites (dùng chung cho nhiều survey, không lồng theo {survey}) ──
    Route::prefix('turnstile-sites')->name('turnstile-sites.')->group(function () {
        Route::get('/',                       [TurnstileSiteController::class, 'index'])->name('index');
        Route::post('/',                      [TurnstileSiteController::class, 'store'])->name('store');
        Route::put('/{turnstileSite}',        [TurnstileSiteController::class, 'update'])->name('update');
        Route::get('/{turnstileSite}/reveal', [TurnstileSiteController::class, 'reveal'])->name('reveal');
        Route::delete('/{turnstileSite}',     [TurnstileSiteController::class, 'destroy'])->name('destroy');
    });

});

// ── Authenticated user: làm khảo sát ─────────────────────────────────────
Route::middleware(['auth', 'verified'])->prefix('dashboard/surveys')->name('backend.surveys.')->group(function () {
    Route::get('/my',                                        [SurveyTakeController::class, 'index'])->name('my');
    Route::get('/{survey:slug}/take',                        [SurveyTakeController::class, 'show'])->name('take');
    Route::post('/{survey:slug}/take',                       [SurveyTakeController::class, 'submit'])->name('take.submit');
    Route::get('/{survey:slug}/take/done/{responseId}',      [SurveyTakeController::class, 'done'])->name('take.done');
});

// ── Public result page for respondents ───────────────────────────────────
Route::get('/surveys/{slug}/result', [\Modules\Survey\Http\Controllers\SurveyResultController::class, 'publicResult'])
    ->name('surveys.result.public')
    ->middleware(\Modules\Survey\Http\Middleware\ValidateSurveyWebToken::class);

// ── Backend JSON API for Tabulator (session-based auth) ───────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('surveys',                          [SurveyBackendApiController::class, 'index'])->name('surveys');
    Route::get('surveys/{survey}/responses',       [SurveyBackendApiController::class, 'responses'])->name('surveys.responses');
});
