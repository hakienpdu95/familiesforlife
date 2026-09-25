<?php

namespace Modules\ProvinceShowcase\Providers;

use App\Models\Province;
use Illuminate\Support\Facades\Gate;
use Modules\ProvinceShowcase\Policies\ProvincePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/Province_Showcase_Technical_Specification.md §6.2 — module này KHÔNG có admin CRUD,
 * chỉ có trang danh sách read-only dashboard/provinces (ProvinceManagement slice).
 */
class ProvinceShowcaseServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'ProvinceShowcase';
    protected string $nameLower = 'provinceshowcase';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Province::class, ProvincePolicy::class);
    }
}
