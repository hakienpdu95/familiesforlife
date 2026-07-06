<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\Backend\EmployerRegistrationController;
use Modules\Marketplace\Http\Controllers\Backend\ListingController;
use Modules\Marketplace\Http\Controllers\Backend\MarketplaceAnalyticsController;
use Modules\Marketplace\Http\Controllers\Backend\OrgApprovalController;
use Modules\Marketplace\Http\Controllers\Api\MktListingApiController;

/*
|--------------------------------------------------------------------------
| Marketplace Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Public portal: Employer registration ─────────────────────────────────
Route::prefix('portal/employer')->name('marketplace.employer.')->group(function () {
    Route::get('register', [EmployerRegistrationController::class, 'create'])->name('register');
    Route::post('register', [EmployerRegistrationController::class, 'store'])->name('register.store');
    Route::get('status', [EmployerRegistrationController::class, 'status'])->name('status');
});

// ── Backend tenant views ──────────────────────────────────────────────────
Route::middleware(['auth', 'feature:module.marketplace'])->prefix('dashboard/marketplace')->name('backend.marketplace.')->group(function () {

    // Listings CRUD
    Route::get('listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
    Route::get('listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::post('listings/{listing}/close', [ListingController::class, 'close'])->name('listings.close');
    Route::delete('listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    // Analytics
    Route::get('analytics', [MarketplaceAnalyticsController::class, 'index'])->name('analytics.index');

    // Admin: org approval queue
    Route::get('org-approvals', [OrgApprovalController::class, 'index'])->name('org-approvals.index');
    Route::post('org-approvals/{org}/approve', [OrgApprovalController::class, 'approve'])->name('org-approvals.approve');
    Route::post('org-approvals/{org}/reject', [OrgApprovalController::class, 'reject'])->name('org-approvals.reject');

});

// ── Backend JSON APIs ─────────────────────────────────────────────────────
Route::middleware(['auth', 'feature:module.marketplace'])->prefix('backend/api/marketplace')->name('backend.api.marketplace.')->group(function () {
    Route::get('listings', [MktListingApiController::class, 'index'])->name('listings');
});
