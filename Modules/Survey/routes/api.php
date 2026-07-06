<?php

use Illuminate\Support\Facades\Route;
use Modules\Survey\Http\Controllers\Api\SurveyApiController;
use Modules\Survey\Http\Middleware\ValidateSurveyToken;
use Modules\Survey\Http\Middleware\ValidateSurveyTurnstile;

/*
|--------------------------------------------------------------------------
| Survey Module — Public API Routes
|--------------------------------------------------------------------------
| /schema  + /submit  → yêu cầu Bearer token (ValidateSurveyToken)
| /submit             → rate limit 60 req/min per token (survey-submit limiter)
| /stats  + /responses → không cần token (internal / reporting)
*/

// middleware('api') đã được RouteServiceProvider gắn — không cần khai báo lại
Route::prefix('v1')->name('surveys.')->group(function () {

    // ── Token-protected endpoints ──────────────────────────────────────
    Route::middleware(ValidateSurveyToken::class)->group(function () {
        Route::get('surveys/{slug}/schema', [SurveyApiController::class, 'schema'])->name('schema');

        Route::middleware(['throttle:survey-submit', ValidateSurveyTurnstile::class])
            ->post('surveys/{slug}/submit', [SurveyApiController::class, 'submit'])
            ->name('submit');

        // stats + responses + result chứa data nhạy cảm → yêu cầu token
        Route::get('surveys/{slug}/stats',     [SurveyApiController::class, 'stats'])->name('stats');
        Route::get('surveys/{slug}/responses', [SurveyApiController::class, 'responses'])->name('responses');

        // T6.1 — Respondent xem kết quả qua Bearer token + ?ref=email
        Route::get('surveys/{slug}/result', [SurveyApiController::class, 'result'])->name('result');

        // T3 (Module 110) — Nhận behavior events sau submit
        Route::post('surveys/{slug}/behavior', [SurveyApiController::class, 'behavior'])->name('behavior');

        // Draft auto-save (cross-device backup)
        Route::post('surveys/{slug}/draft', [SurveyApiController::class, 'saveDraft'])->name('draft.save');
        Route::get('surveys/{slug}/draft',  [SurveyApiController::class, 'getDraft'])->name('draft.get');

        // GDPR self-service erasure
        Route::delete('surveys/{slug}/my-data', [SurveyApiController::class, 'eraseMyData'])->name('my-data.erase');
    });

});
