<?php

namespace Modules\ProvinceShowcase\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/Province_Showcase_Technical_Specification.md §6.2 — module này KHÔNG có admin CRUD,
 * chỉ migration (slug)/config/route công khai/view — không cần đăng ký Policy nào.
 */
class ProvinceShowcaseServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'ProvinceShowcase';
    protected string $nameLower = 'provinceshowcase';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
