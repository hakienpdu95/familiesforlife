<?php

namespace Modules\Menu\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Modules\Menu\Console\Commands\BackfillMenuFromCategoriesCommand;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Policies\MenuItemPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MenuServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Menu';
    protected string $nameLower = 'menu';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        BackfillMenuFromCategoriesCommand::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(MenuItem::class, MenuItemPolicy::class);

        // spec/Menu_Navigation_Technical_Specification.md §7.1 — nav công khai (Phase 3) đọc
        // MenuItem::tree() qua composer dùng chung, thay vì mỗi controller Post/Event tự gọi
        // PostCategory::navTree(). $categories (PostCategory) vẫn được các controller truyền
        // riêng cho mục đích KHÁC nav (vd promo-bar/cta-band ở trang chủ) — không đụng tới.
        // once(): 'layouts.frontend' (JSON-LD §7.2.1) + 'frontend-nav' + 'frontend-drawer' cùng
        // render trên 1 trang → composer này chạy 3 lần/request cho cùng 1 callback — memoize
        // để chỉ 1 query MenuItem::tree().
        View::composer(
            ['layouts.frontend', 'layouts.partials.frontend-nav', 'layouts.partials.frontend-drawer'],
            fn ($view) => $view->with('menuTree', once(fn () => MenuItem::tree('header')))
        );

        View::composer(
            'layouts.partials.frontend-footer',
            fn ($view) => $view->with('footerMenuTree', MenuItem::tree('footer'))
        );
    }
}
