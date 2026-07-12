<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\OrganizationApiController;
use Modules\Organization\Http\Controllers\Api\OrganizationLogoController;
use Modules\Organization\Http\Controllers\OrganizationController;

/*
|--------------------------------------------------------------------------
| Organization Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD (admin panel — System_Admin / super-admin only) ───────────
// Authorization được enforce qua OrganizationPolicy::authorizeResource() trong constructor.
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('organizations', OrganizationController::class);

    // Platform Approval Gateway — content_moderator duyệt hồ sơ tổ chức
    // (spec/Workflow_Approval_Technical_Specification.md — Case study kiểm duyệt xuyên tổ chức)
    Route::post('organizations/{organization}/submit-approval', [OrganizationController::class, 'submitApproval'])->name('organizations.submit-approval');
    Route::post('organizations/{organization}/approve-content', [OrganizationController::class, 'approveContent'])->name('organizations.approve-content');
    Route::post('organizations/{organization}/reject-content', [OrganizationController::class, 'rejectContent'])->name('organizations.reject-content');
    Route::post('organizations/{organization}/publish-content', [OrganizationController::class, 'publishContent'])->name('organizations.publish-content');
    Route::post('organizations/{organization}/archive-content', [OrganizationController::class, 'archiveContent'])->name('organizations.archive-content');
});

// ── Backend JSON API for Tabulator (session-based auth, same guard as admin panel) ─────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('organizations', [OrganizationApiController::class, 'index'])
        ->name('organizations');

    // Logo upload / delete
    Route::post('organizations/{organization}/logo',   [OrganizationLogoController::class, 'store'])->name('organizations.logo.store');
    Route::delete('organizations/{organization}/logo', [OrganizationLogoController::class, 'destroy'])->name('organizations.logo.destroy');
});
