<?php

use Illuminate\Support\Facades\Route;
use Modules\PensionCalculator\Features\ParameterManagement\Http\PensionParameterAdminController;
use Modules\PensionCalculator\Features\PublicEstimation\Http\PensionCalculatorController;

// spec/bhxh/PensionCalculator_Technical_Specification.md §5

// ── Public — không auth, giống /anland ──────────────────────────────────
Route::get('tinh-luong-huu-bhxh-tu-nguyen', [PensionCalculatorController::class, 'index'])
    ->name('pension-calculator.public.index');

Route::get('api/pension-calculator/reference-data', [PensionCalculatorController::class, 'referenceData'])
    ->middleware('throttle:60,1') // chỉ đọc dữ liệu công khai, không nhận input cá nhân — throttle chống scrape/abuse thô, không phải bảo mật dữ liệu nhạy cảm
    ->name('pension-calculator.public.reference-data');

// Bài toán #27 (spec/giadinh.md) — log thống kê TỔNG HỢP, ẨN DANH, opt-in (xem migration
// pension_usage_logs + PensionCalculatorController::logUsage()). Throttle chặt hơn reference-data
// vì đây là ghi (write), không phải đọc — 10 lần/phút đủ cho 1 người dùng thật gửi vài lần.
Route::post('api/pension-calculator/log-usage', [PensionCalculatorController::class, 'logUsage'])
    ->middleware('throttle:10,1')
    ->name('pension-calculator.public.log-usage');

// ── Admin — quản lý tham số ──────────────────────────────────────────────
// index() dùng 'permission:...|...' (Spatie — user cần BẤT KỲ permission nào trong danh sách)
// để CEO (chỉ có pension_calculator.view, §9.3) cũng xem được, còn create/store vẫn khoá riêng
// bằng 'can:pension_calculator.manage' (chỉ System_Admin) — spec §5 chỉ liệt kê 1 middleware
// group dùng chung 'can:pension_calculator.manage' cho toàn bộ route, nhưng làm vậy sẽ khoá
// luôn CEO khỏi index() dù §9.3 mô tả rõ CEO "chỉ xem" — tách middleware theo route để đúng
// tinh thần §9.3 thay vì đúng y nguyên đoạn code minh hoạ ở §5.
Route::middleware(['auth'])
    ->prefix('dashboard/pension-calculator')
    ->name('backend.pension-calculator.')
    ->group(function (): void {
        Route::get('/', [PensionParameterAdminController::class, 'index'])
            ->middleware('permission:pension_calculator.manage|pension_calculator.view')
            ->name('index');

        Route::middleware('can:pension_calculator.manage')->group(function (): void {
            Route::get('periods/create', [PensionParameterAdminController::class, 'createPeriod'])->name('periods.create');
            Route::post('periods', [PensionParameterAdminController::class, 'storePeriod'])->name('periods.store');
            Route::get('price-index/create', [PensionParameterAdminController::class, 'createPriceIndex'])->name('price-index.create');
            Route::post('price-index', [PensionParameterAdminController::class, 'storePriceIndex'])->name('price-index.store');
            Route::get('rate-brackets/create', [PensionParameterAdminController::class, 'createRateBracket'])->name('rate-brackets.create');
            Route::post('rate-brackets', [PensionParameterAdminController::class, 'storeRateBracket'])->name('rate-brackets.store');
            // Không có "edit"/"update"/"destroy" cho dữ liệu ĐÃ CÓ HIỆU LỰC (§9.2 — bất biến,
            // giống lý do snapshot Assessment không sửa ngược lịch sử).
        });
    });
