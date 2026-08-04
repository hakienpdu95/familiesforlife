<?php

namespace Modules\PensionCalculator\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §4 — module không tenant-scoped
 * (tham số BHXH tự nguyện áp dụng thống nhất toàn quốc, không theo Organization) và không lưu
 * dữ liệu cá nhân người dùng (§0) — chỉ 4 model tham chiếu công khai (Models/*).
 */
class PensionCalculatorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'PensionCalculator';

    protected string $nameLower = 'pensioncalculator';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
