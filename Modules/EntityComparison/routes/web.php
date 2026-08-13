<?php

use Illuminate\Support\Facades\Route;
use Modules\EntityComparison\Features\Comparison\Http\EntityComparisonController;
use Modules\EntityComparison\Features\CriterionManagement\Http\CriterionAdminController;
use Modules\EntityComparison\Features\CriterionManagement\Http\CriterionApiController;
use Modules\EntityComparison\Features\EntityManagement\Http\EntityAdminController;
use Modules\EntityComparison\Features\EntityManagement\Http\EntityApiController;
use Modules\EntityComparison\Features\EntityTypeManagement\Http\EntityTypeAdminController;
use Modules\EntityComparison\Features\EntityTypeManagement\Http\EntityTypeApiController;
use Modules\EntityComparison\Features\PublicFiltering\Http\PublicEntityComparisonController;

// spec/Entity_Comparison_Module_Technical_Spec.md §8 — admin CRUD, đúng convention
// dashboard/ocop/products (resource, except show).
Route::middleware(['auth'])->prefix('dashboard/entity-comparison')->name('backend.entity_comparison.')->group(function (): void {
    // ->names('entity_types') — Route::resource() mặc định lấy tên route từ URI segment
    // ('entity-types', có gạch ngang), khác param binding ('entity_type', gạch dưới do
    // ResourceRegistrar tự đổi '-' thành '_' cho tên biến PHP hợp lệ). Không ép tên route cũng
    // dùng gạch dưới thì route('...entity_types.index') ở mọi Blade view/redirect bên dưới sẽ
    // không khớp route thật ('...entity-types.index') — route() sẽ ném RouteNotFoundException.
    Route::resource('entity-types', EntityTypeAdminController::class)->except(['show'])->names('entity_types');

    // §7.1 mục 3 — gán/bỏ gán Criteria cho 1 EntityType, tách khỏi resource CRUD chuẩn.
    Route::get('entity-types/{entity_type}/criteria', [EntityTypeAdminController::class, 'editCriteria'])
        ->name('entity_types.criteria.edit');
    Route::put('entity-types/{entity_type}/criteria', [EntityTypeAdminController::class, 'updateCriteria'])
        ->name('entity_types.criteria.update');

    Route::resource('criteria', CriterionAdminController::class)
        ->except(['show'])
        ->parameters(['criteria' => 'criterion']);

    Route::resource('entities', EntityAdminController::class)->except(['show']);
});

// ── Backend JSON API — cùng convention backend/api/ocop (session-based auth, cùng guard trang
// quản trị) — tham chiếu Modules/Ocop/routes/web.php ───────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api/entity-comparison')->name('backend.api.entity_comparison.')->group(function (): void {
    Route::get('entities', [EntityApiController::class, 'index'])->name('entities');
    Route::get('criteria', [CriterionApiController::class, 'index'])->name('criteria');
    Route::get('entity-types', [EntityTypeApiController::class, 'index'])->name('entity_types');
});

// ── Cổng thông tin công khai — KHÔNG yêu cầu đăng nhập (§0 mục 9). EntityType bind theo slug
// (getRouteKeyName), Entity bind theo uuid — xem Models. Không có trang chi tiết riêng cho 1
// Entity ở v1.0 (§0 mục 11) nên KHÔNG cần route suffix marker mới kiểu Post '-d'/Event '-sk'/
// Ocop '-op'. ────────────────────────────────────────────────────────────────────────────────
Route::get('entity-comparison/{entityType}', [PublicEntityComparisonController::class, 'index'])
    ->name('entity_comparison.public.index');

Route::get('entity-comparison/{entityType}/compare', [EntityComparisonController::class, 'show'])
    ->name('entity_comparison.public.compare');
