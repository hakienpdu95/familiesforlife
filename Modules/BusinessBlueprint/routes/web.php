<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Http\Controllers\BlueprintAuthoringController;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Http\Controllers\BlueprintController;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Http\Controllers\BlueprintVersionController;

Route::middleware(['web', 'auth', 'can:' . P::BLUEPRINT_VIEW->value])
    ->prefix('dashboard/business-blueprint/admin')
    ->name('business_blueprint.admin.')
    ->group(function (): void {
        Route::get('/', [BlueprintController::class, 'index'])->name('index');
        Route::get('create', [BlueprintController::class, 'create'])->middleware('can:' . P::BLUEPRINT_CREATE->value)->name('create');
        Route::post('/', [BlueprintController::class, 'store'])->middleware('can:' . P::BLUEPRINT_CREATE->value)->name('store');
        Route::get('{blueprint}/edit', [BlueprintController::class, 'edit'])->middleware('can:' . P::BLUEPRINT_EDIT->value)->name('edit');
        Route::put('{blueprint}', [BlueprintController::class, 'update'])->middleware('can:' . P::BLUEPRINT_EDIT->value)->name('update');
        Route::delete('{blueprint}', [BlueprintController::class, 'destroy'])->middleware('can:' . P::BLUEPRINT_DELETE->value)->name('destroy');

        Route::prefix('{blueprint}/versions')->name('versions.')->group(function (): void {
            Route::get('/', [BlueprintVersionController::class, 'index'])->name('index'); // Version Manager (A04.3 §12)
            Route::get('{version}/tree', [BlueprintAuthoringController::class, 'tree'])->name('tree');
            Route::get('{version}/validate', [BlueprintAuthoringController::class, 'validateIntegrity'])->name('validate');
            Route::get('{version}/readiness', [BlueprintAuthoringController::class, 'readiness'])->name('readiness');
            Route::post('{version}/clone', [BlueprintVersionController::class, 'clone'])->middleware('can:' . P::BLUEPRINT_CLONE->value)->name('clone');
            Route::post('{version}/publish', [BlueprintVersionController::class, 'publish'])->middleware('can:' . P::BLUEPRINT_PUBLISH->value)->name('publish');
            Route::post('{version}/archive', [BlueprintVersionController::class, 'archive'])->middleware('can:' . P::BLUEPRINT_ARCHIVE->value)->name('archive');
            Route::get('compare', [BlueprintVersionController::class, 'compare'])->name('compare'); // ?from=1&to=2
        });

        // CRUD lồng nhau cho outcome/capability/workflow/phase/checklist/resource-link/ai-capability/analytic/role/sidebar-item
        Route::middleware('can:' . P::BLUEPRINT_EDIT->value)->group(function (): void {
            Route::post('outcomes', [BlueprintAuthoringController::class, 'storeOutcome'])->name('outcomes.store');
            Route::put('outcomes/{outcome}', [BlueprintAuthoringController::class, 'updateOutcome'])->name('outcomes.update');
            Route::delete('outcomes/{outcome}', [BlueprintAuthoringController::class, 'destroyOutcome'])->name('outcomes.destroy');

            Route::post('capabilities', [BlueprintAuthoringController::class, 'storeCapability'])->name('capabilities.store');
            Route::put('capabilities/{capability}', [BlueprintAuthoringController::class, 'updateCapability'])->name('capabilities.update');
            Route::delete('capabilities/{capability}', [BlueprintAuthoringController::class, 'destroyCapability'])->name('capabilities.destroy');

            Route::post('workflows', [BlueprintAuthoringController::class, 'storeWorkflow'])->name('workflows.store');
            Route::put('workflows/{workflow}', [BlueprintAuthoringController::class, 'updateWorkflow'])->name('workflows.update');
            Route::delete('workflows/{workflow}', [BlueprintAuthoringController::class, 'destroyWorkflow'])->name('workflows.destroy');

            Route::post('phases', [BlueprintAuthoringController::class, 'storePhase'])->name('phases.store');
            Route::put('phases/{phase}', [BlueprintAuthoringController::class, 'updatePhase'])->name('phases.update');
            Route::delete('phases/{phase}', [BlueprintAuthoringController::class, 'destroyPhase'])->name('phases.destroy');

            Route::post('checklists', [BlueprintAuthoringController::class, 'storeChecklist'])->name('checklists.store');
            Route::put('checklists/{checklist}', [BlueprintAuthoringController::class, 'updateChecklist'])->name('checklists.update');
            Route::delete('checklists/{checklist}', [BlueprintAuthoringController::class, 'destroyChecklist'])->name('checklists.destroy');

            Route::post('resource-links', [BlueprintAuthoringController::class, 'storeResourceLink'])->name('resource_links.store');
            Route::put('resource-links/{resourceLink}', [BlueprintAuthoringController::class, 'updateResourceLink'])->name('resource_links.update');
            Route::delete('resource-links/{resourceLink}', [BlueprintAuthoringController::class, 'destroyResourceLink'])->name('resource_links.destroy');

            Route::post('ai-capabilities', [BlueprintAuthoringController::class, 'storeAiCapability'])->name('ai_capabilities.store');
            Route::put('ai-capabilities/{aiCapability}', [BlueprintAuthoringController::class, 'updateAiCapability'])->name('ai_capabilities.update');
            Route::delete('ai-capabilities/{aiCapability}', [BlueprintAuthoringController::class, 'destroyAiCapability'])->name('ai_capabilities.destroy');

            Route::post('analytics', [BlueprintAuthoringController::class, 'storeAnalytic'])->name('analytics.store');
            Route::put('analytics/{analytic}', [BlueprintAuthoringController::class, 'updateAnalytic'])->name('analytics.update');
            Route::delete('analytics/{analytic}', [BlueprintAuthoringController::class, 'destroyAnalytic'])->name('analytics.destroy');

            Route::post('deployment-roles', [BlueprintAuthoringController::class, 'storeDeploymentRole'])->name('deployment_roles.store');
            Route::put('deployment-roles/{role}', [BlueprintAuthoringController::class, 'updateDeploymentRole'])->name('deployment_roles.update');
            Route::delete('deployment-roles/{role}', [BlueprintAuthoringController::class, 'destroyDeploymentRole'])->name('deployment_roles.destroy');

            Route::post('sidebar-items', [BlueprintAuthoringController::class, 'storeSidebarItem'])->name('sidebar_items.store');
            Route::put('sidebar-items/{sidebarItem}', [BlueprintAuthoringController::class, 'updateSidebarItem'])->name('sidebar_items.update');
            Route::delete('sidebar-items/{sidebarItem}', [BlueprintAuthoringController::class, 'destroySidebarItem'])->name('sidebar_items.destroy');
        });
    });
