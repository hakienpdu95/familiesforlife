<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Features\ListingManagement\Http\RealEstateListingAdminController;
use Modules\RealEstate\Features\ListingManagement\Http\RealEstateListingApiController;
use Modules\RealEstate\Features\PublicReading\Http\PublicRealEstateController;

// ── Admin (Organization) — spec/RealEstateForSale_Technical_Specification.md §7.1 ──────────
// middleware ['auth','tenant'] giống Modules/Product (RealEstateListing tenant-scoped).
Route::middleware(['auth', 'tenant'])->prefix('dashboard/real-estate')->name('backend.real-estate.')
    ->group(function (): void {
        Route::get('/', [RealEstateListingAdminController::class, 'index'])->name('index');
        Route::get('create', [RealEstateListingAdminController::class, 'create'])->name('create');
        Route::post('/', [RealEstateListingAdminController::class, 'store'])->name('store');
        Route::get('{listing}/edit', [RealEstateListingAdminController::class, 'edit'])->name('edit');
        Route::put('{listing}', [RealEstateListingAdminController::class, 'update'])->name('update');
        Route::delete('{listing}', [RealEstateListingAdminController::class, 'destroy'])->name('destroy');
        Route::post('{listing}/gallery/reorder', [RealEstateListingAdminController::class, 'reorderGallery'])->name('gallery.reorder');

        // Approval workflow — copy đúng cấu trúc route Modules/Product (§5.5/§6 spec Bán).
        Route::post('{listing}/submit-approval', [RealEstateListingAdminController::class, 'submitApproval'])->name('submit-approval');
        Route::post('{listing}/approve-content', [RealEstateListingAdminController::class, 'approveContent'])->name('approve-content');
        Route::post('{listing}/reject-content', [RealEstateListingAdminController::class, 'rejectContent'])->name('reject-content');
        Route::post('{listing}/publish-content', [RealEstateListingAdminController::class, 'publishContent'])->name('publish-content');
        Route::post('{listing}/archive-content', [RealEstateListingAdminController::class, 'archiveContent'])->name('archive-content');
    });

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị, tenant-scoped
// tự động qua TenantAwareModel) — tham chiếu Modules/Product/routes/web.php (backend.api.products) ──
Route::middleware(['auth', 'tenant'])->prefix('backend/api/real-estate')->name('backend.api.real-estate.')->group(function (): void {
    Route::get('listings', [RealEstateListingApiController::class, 'index'])->name('listings');
});

// ── Public — §7.1/§0 spec Bán, KHÔNG middleware auth ────────────────────────────────────────
Route::get('nha-dat-ban', [PublicRealEstateController::class, 'saleIndex'])->name('real-estate.public.sale.index');
Route::get('nha-dat-ban/{slug}-r{id}.html', [PublicRealEstateController::class, 'saleShow'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])->name('real-estate.public.sale.show');

// spec/RealEstateForRent_Technical_Specification.md §4.1
Route::get('nha-dat-thue', [PublicRealEstateController::class, 'rentIndex'])->name('real-estate.public.rent.index');
Route::get('nha-dat-thue/{slug}-r{id}.html', [PublicRealEstateController::class, 'rentShow'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])->name('real-estate.public.rent.show');
