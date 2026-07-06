<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\Deployment\Http\Controllers\DeploymentChecklistController;
use Modules\Deployment\Http\Controllers\DeploymentEngineController;
use Modules\Deployment\Http\Controllers\DeploymentDashboardController;
use Modules\Deployment\Http\Controllers\DeploymentIssueController;
use Modules\Deployment\Http\Controllers\DeploymentLandingController;
use Modules\Deployment\Http\Controllers\DeploymentProgressController;
use Modules\Deployment\Http\Controllers\DeploymentProjectController;
use Modules\Deployment\Http\Controllers\DeploymentReportController;
use Modules\Deployment\Http\Controllers\DeploymentTargetController;
use Modules\Deployment\Http\Controllers\DeploymentDataCollectionController;
use Modules\Deployment\Http\Controllers\DeploymentExportController;
use Modules\Deployment\Http\Controllers\DeploymentMediaController;
use Modules\Deployment\Http\Controllers\DeploymentReadinessController;
use Modules\Deployment\Http\Controllers\DeploymentValidatorController;

/*
|--------------------------------------------------------------------------
| Deployment Module — Web Routes
| URL pattern : /dashboard/{vertical}/...
|--------------------------------------------------------------------------
*/

// ── Landing page (hub) — no vertical context needed ──────────────────────────
Route::middleware(['auth', 'tenant'])
    ->get('/dashboard/deployment', [DeploymentLandingController::class, 'index'])
    ->name('deployment.landing');

Route::middleware(['auth', 'tenant'])
    ->prefix('dashboard/{vertical}')
    ->name('deployment.')
    ->group(function () {

        Route::middleware('vertical')->group(function () {

            // ── Dashboard ──────────────────────────────────────────────────────
            Route::get('/dashboard', [DeploymentDashboardController::class, 'index'])
                ->name('dashboard');

            // ── Projects ──────────────────────────────────────────────────────
            Route::prefix('projects')->name('projects.')->group(function () {
                Route::get('/',        [DeploymentProjectController::class, 'index'])->name('index');
                Route::get('/create',  [DeploymentProjectController::class, 'create'])->name('create');
                Route::post('/',       [DeploymentProjectController::class, 'store'])->name('store');
            });

            // ── Targets ───────────────────────────────────────────────────────
            Route::prefix('targets')->name('targets.')->group(function () {
                Route::get('/lookup',            [DeploymentTargetController::class, 'lookup'])->name('lookup');
                Route::get('/organization-slugs', [DeploymentTargetController::class, 'organizationSlugs'])->name('organization-slugs');
                Route::get('/',                  [DeploymentTargetController::class, 'index'])->name('index');
                Route::get('/create',            [DeploymentTargetController::class, 'create'])->name('create');
                Route::post('/',                 [DeploymentTargetController::class, 'store'])->name('store');
                Route::get('/{target}',          [DeploymentTargetController::class, 'show'])->name('show');
                Route::post('/{target}/advance', [DeploymentTargetController::class, 'advance'])->name('advance');
                Route::patch('/{target}/assign', [DeploymentTargetController::class, 'assignEmployee'])->name('assign');
            });

            // ── Checklist ─────────────────────────────────────────────────────
            Route::post('checklist/{item}/toggle', [DeploymentChecklistController::class, 'toggle'])
                ->name('checklist.toggle');
            Route::post('checklist/{item}/note', [DeploymentChecklistController::class, 'addNote'])
                ->name('checklist.note');
            Route::patch('checklist/{item}/assign', [DeploymentChecklistController::class, 'assignEmployee'])
                ->name('checklist.assign');

            // ── Validator ─────────────────────────────────────────────────────
            Route::prefix('validator')->name('validator.')->group(function () {
                Route::post('targets/{target}/run',   [DeploymentValidatorController::class, 'run'])->name('run');
                Route::get('targets/{target}/score',  [DeploymentValidatorController::class, 'score'])->name('score');
            });

            // ── Issues ────────────────────────────────────────────────────────
            Route::prefix('issues')->name('issues.')->group(function () {
                Route::get('/',                  [DeploymentIssueController::class, 'index'])->name('index');
                Route::get('/create',            [DeploymentIssueController::class, 'create'])->name('create');
                Route::post('/',                 [DeploymentIssueController::class, 'store'])->name('store');
                Route::get('/{issue}',           [DeploymentIssueController::class, 'show'])->name('show');
                Route::patch('/{issue}',         [DeploymentIssueController::class, 'update'])->name('update');
                Route::patch('/{issue}/assign',  [DeploymentIssueController::class, 'assignOwner'])->name('assign');
                Route::post('/{issue}/resolve',  [DeploymentIssueController::class, 'resolve'])->name('resolve');
            });

            // ── Progress Logs ─────────────────────────────────────────────────
            Route::prefix('progress')->name('progress.')->group(function () {
                Route::get('/',  [DeploymentProgressController::class, 'index'])->name('index');
                Route::post('/', [DeploymentProgressController::class, 'store'])->name('store');
            });

            // ── Readiness Assessment ──────────────────────────────────────────
            Route::prefix('readiness')->name('readiness.')->group(function () {
                Route::post('targets/{target}/start',  [DeploymentReadinessController::class, 'start'])->name('start');
                Route::get('targets/{target}/fill',    [DeploymentReadinessController::class, 'fill'])->name('fill');
                Route::post('targets/{target}/submit', [DeploymentReadinessController::class, 'submit'])->name('submit');
                Route::get('targets/{target}',         [DeploymentReadinessController::class, 'show'])->name('show');
                Route::get('targets/{target}/score',   [DeploymentReadinessController::class, 'score'])->name('score');
            });

            // ── Data Collection (Survey-based) ────────────────────────────────
            Route::prefix('targets/{target}')->name('data-collection.')->group(function () {
                Route::get('collect',                    [DeploymentDataCollectionController::class, 'show'])->name('show');
                Route::post('collect/{sectionCode}',     [DeploymentDataCollectionController::class, 'submitSection'])->name('submit-section');
            });

            // ── Media (Organization MediaLibrary) ─────────────────────────────
            Route::prefix('targets/{target}/media')->name('media.')->group(function () {
                Route::get('/',         [DeploymentMediaController::class, 'index'])->name('index');
                Route::post('/',        [DeploymentMediaController::class, 'store'])->name('store');
                Route::delete('/{mediaId}', [DeploymentMediaController::class, 'destroy'])->name('destroy');
            });

            // ── Export (Survey-based field picker) ───────────────────────────
            Route::prefix('export')->name('export.')->group(function () {
                Route::get('template/{type}',                   [DeploymentExportController::class, 'template'])->name('template');
                Route::get('targets/{target}',                  [DeploymentExportController::class, 'index'])->name('index');
                Route::post('targets/{target}/download',        [DeploymentExportController::class, 'exportTarget'])->name('target');
                Route::get('projects/{project}',                [DeploymentExportController::class, 'projectIndex'])->name('project-index');
                Route::post('projects/{project}/download',      [DeploymentExportController::class, 'exportProject'])->name('project');
            });

            // ── Reports ───────────────────────────────────────────────────────
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/pm',       [DeploymentReportController::class, 'pm'])->name('pm');
                Route::get('/province', [DeploymentReportController::class, 'province'])->name('province');
            });

        });
    });

// ── Mobile routes (separate middleware stack — no vertical requirement) ────────
Route::middleware(['auth', 'tenant'])
    ->prefix('m/{vertical}')
    ->name('deployment.mobile.')
    ->group(function () {
        Route::middleware('vertical')->group(function () {
            Route::get('/checklist/{target}', [DeploymentChecklistController::class, 'mobile'])
                ->name('checklist');
        });
    });

/*
|--------------------------------------------------------------------------
| Deployment Engine — spec §4.5/§4.7 (DeployOrganizationSolutionAction)
| Không dùng {vertical} URL — chạy trên OrganizationSolution/Deployment trực tiếp.
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('dashboard/deployments')
    ->name('deployments.')
    ->group(function (): void {
        Route::post('organization-solutions/{organizationSolution}/deploy', [DeploymentEngineController::class, 'deploy'])
            ->middleware('can:' . P::DEPLOYMENT_RUN->value)
            ->name('deploy');
        Route::get('{deployment}/logs', [DeploymentEngineController::class, 'logs'])
            ->middleware('can:' . P::DEPLOYMENT_VIEW_LOGS->value)
            ->name('logs');
        Route::get('{deployment}/snapshots', [DeploymentEngineController::class, 'snapshots'])
            ->middleware('can:' . P::DEPLOYMENT_VIEW_LOGS->value)
            ->name('snapshots');
    });
