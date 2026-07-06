<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\OrganizationSolution\Features\SolutionActivation\Http\Controllers\OrganizationSolutionController;
use Modules\OrganizationSolution\Features\SolutionActivation\Http\Controllers\SolutionWizardController;

Route::middleware(['web', 'auth', 'tenant', 'can:' . P::SOLUTION_ACTIVATE->value])
    ->prefix('dashboard/organization-solutions')
    ->name('organization_solutions.')
    ->group(function (): void {
        Route::get('/', [OrganizationSolutionController::class, 'index'])->name('index');
        Route::post('activate', [OrganizationSolutionController::class, 'activate'])->name('activate');
        Route::post('{organizationSolution}/suspend', [OrganizationSolutionController::class, 'suspend'])->middleware('can:' . P::SOLUTION_SUSPEND->value)->name('suspend');
        Route::post('{organizationSolution}/archive', [OrganizationSolutionController::class, 'archive'])->middleware('can:' . P::SOLUTION_ARCHIVE->value)->name('archive');

        Route::prefix('{organizationSolution}/wizard')
            ->name('wizard.')
            ->middleware('can:' . P::SOLUTION_CONFIGURE->value)
            ->group(function (): void {
                Route::get('capabilities', [SolutionWizardController::class, 'showCapabilities'])->name('capabilities.form');
                Route::post('capabilities', [SolutionWizardController::class, 'capabilities'])->name('capabilities');

                Route::get('workflows', [SolutionWizardController::class, 'showWorkflows'])->name('workflows.form');
                Route::post('workflows', [SolutionWizardController::class, 'workflows'])->name('workflows');

                Route::get('checklists', [SolutionWizardController::class, 'showChecklists'])->name('checklists.form');
                Route::post('checklists', [SolutionWizardController::class, 'checklists'])->name('checklists');

                Route::get('resources', [SolutionWizardController::class, 'showResources'])->name('resources.form');
                Route::post('resources', [SolutionWizardController::class, 'resources'])->name('resources');

                Route::get('ai', [SolutionWizardController::class, 'showAi'])->name('ai.form');
                Route::post('ai', [SolutionWizardController::class, 'ai'])->name('ai');

                Route::get('roles', [SolutionWizardController::class, 'showRoles'])->name('roles.form');
                Route::post('roles', [SolutionWizardController::class, 'roles'])->name('roles');

                Route::get('dashboard', [SolutionWizardController::class, 'showDashboard'])->name('dashboard.form');
                Route::post('dashboard', [SolutionWizardController::class, 'dashboard'])->name('dashboard');

                Route::get('review', [SolutionWizardController::class, 'showReview'])->name('review.form');
                Route::post('review', [SolutionWizardController::class, 'review'])->name('review');
            });
    });
