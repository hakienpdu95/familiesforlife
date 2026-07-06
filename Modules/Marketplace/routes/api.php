<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\Api\Org\OrgApplicantApiController;
use Modules\Marketplace\Http\Controllers\Api\Org\OrgListingApiController;
use Modules\Marketplace\Http\Controllers\Api\Portal\ApplicantAuthController;
use Modules\Marketplace\Http\Controllers\Api\Portal\ApplicantProfileController;
use Modules\Marketplace\Http\Controllers\Api\Portal\MktApplicationController;
use Modules\Marketplace\Http\Controllers\Api\Portal\MktBookmarkController;
use Modules\Marketplace\Http\Controllers\Api\Portal\PublicListingController;

/*
|--------------------------------------------------------------------------
| Marketplace Portal API — all routes prefixed with /api (from RouteServiceProvider)
|--------------------------------------------------------------------------
*/

// ── Public (no auth) ─────────────────────────────────────────────────────
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('listings',                 [PublicListingController::class, 'index'])->name('listings.index');
    Route::get('listings/{slug}',          [PublicListingController::class, 'show'])->name('listings.show');
    Route::get('listings/{slug}/similar',  [PublicListingController::class, 'similar'])->name('listings.similar');
    Route::get('profiles/{slug}',          [PublicListingController::class, 'profile'])->name('profiles.show');
    Route::get('tags',                     [PublicListingController::class, 'tags'])->name('tags.index');
});

// ── Applicant Auth (marketplace guard) ──────────────────────────────────
Route::prefix('portal/auth')->name('portal.auth.')->group(function () {
    Route::post('register', [ApplicantAuthController::class, 'register'])->name('register');
    Route::post('login',    [ApplicantAuthController::class, 'login'])->name('login');
    Route::post('logout',   [ApplicantAuthController::class, 'logout'])->middleware('auth:marketplace')->name('logout');
});

// ── Applicant Protected (marketplace guard) ──────────────────────────────
Route::prefix('portal/me')->name('portal.me.')->middleware('auth:marketplace')->group(function () {
    Route::get('profile',          [ApplicantProfileController::class, 'show'])->name('profile.show');
    Route::put('profile',          [ApplicantProfileController::class, 'update'])->name('profile.update');
    Route::post('skills',          [ApplicantProfileController::class, 'addSkill'])->name('skills.store');
    Route::post('experiences',     [ApplicantProfileController::class, 'addExperience'])->name('experiences.store');
    Route::post('portfolios',      [ApplicantProfileController::class, 'addPortfolio'])->name('portfolios.store');
    Route::get('applications',     [MktApplicationController::class, 'myApplications'])->name('applications.index');
    Route::get('bookmarks',        [MktBookmarkController::class, 'myBookmarks'])->name('bookmarks.index');
});

// ── Applicant Actions (marketplace guard) ────────────────────────────────
Route::prefix('portal')->name('portal.')->middleware('auth:marketplace')->group(function () {
    Route::post('listings/{slug}/apply',         [MktApplicationController::class, 'apply'])->name('listings.apply');
    Route::post('listings/{slug}/bookmark',      [MktBookmarkController::class, 'toggle'])->name('listings.bookmark');
    Route::post('applications/{uuid}/withdraw',  [MktApplicationController::class, 'withdraw'])->name('applications.withdraw');
});

// ── Org (web guard — internal users) ────────────────────────────────────
Route::prefix('marketplace/org')->name('marketplace.org.')->middleware('auth')->group(function () {
    Route::get('listings',                         [OrgListingApiController::class, 'index'])->name('listings.index');
    Route::post('listings',                        [OrgListingApiController::class, 'store'])->name('listings.store');
    Route::put('listings/{listing}',               [OrgListingApiController::class, 'update'])->name('listings.update');
    Route::post('listings/{listing}/close',        [OrgListingApiController::class, 'close'])->name('listings.close');
    Route::post('listings/{listing}/resync',       [OrgListingApiController::class, 'resync'])->name('listings.resync');
    Route::get('listings/{listing}/applicants',    [OrgApplicantApiController::class, 'listApplicants'])->name('listings.applicants');
    Route::post('applications/{application}/shortlist', [OrgApplicantApiController::class, 'shortlist'])->name('applications.shortlist');
    Route::post('applications/{application}/reject',    [OrgApplicantApiController::class, 'reject'])->name('applications.reject');
    Route::post('applications/{application}/import',    [OrgApplicantApiController::class, 'import'])->name('applications.import');
});

// ── Applicant Reviews (marketplace guard) ───────────────────────────────
Route::prefix('portal')->name('portal.')->middleware('auth:marketplace')->group(function () {
    Route::post('applications/{uuid}/review', [\Modules\Marketplace\Http\Controllers\Api\Portal\MktReviewController::class, 'store'])->name('applications.review');
});

// ── Public Reviews ──────────────────────────────────────────────────────
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('profiles/{slug}/reviews', [\Modules\Marketplace\Http\Controllers\Api\Portal\MktReviewController::class, 'profileReviews'])->name('profiles.reviews');
    Route::get('listings/trending',       [PublicListingController::class, 'trending'])->name('listings.trending');
});

// ── Org Reviews & Analytics ─────────────────────────────────────────────
Route::prefix('marketplace/org')->name('marketplace.org.')->middleware('auth')->group(function () {
    Route::post('applications/{application}/review', [\Modules\Marketplace\Http\Controllers\Api\Org\OrgReviewController::class, 'store'])->name('applications.review');
    Route::get('analytics',   [\Modules\Marketplace\Http\Controllers\Api\Org\OrgAnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('sync-status', [\Modules\Marketplace\Http\Controllers\Api\Org\OrgAnalyticsController::class, 'syncStatus'])->name('analytics.sync-status');
});
