<?php

namespace Modules\Organization\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Modules\Organization\Http\Middleware\SetCurrentOrganization;
use Modules\Organization\Models\Organization;
use Modules\Organization\Policies\OrganizationPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class OrganizationServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Organization';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'organization';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register(); // registers RouteServiceProvider
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Organization::class, OrganizationPolicy::class);
        // Đăng ký thêm cho class GỐC — RegisterOrganizationAction (Modules/Auth) và
        // config/approval.php dùng App\Shared\Tenancy\Models\Organization trực tiếp, không
        // qua subclass này. Gate::policy() chỉ khớp CHÍNH XÁC theo class, không tự "đi lên"
        // theo cây kế thừa — thiếu dòng này, $user->can('approve', $organization) trên 1
        // instance class gốc luôn trả false (không tìm thấy Policy nào khớp), bug thật phát
        // hiện khi content_moderator không duyệt được tổ chức mới đăng ký.
        Gate::policy(\App\Shared\Tenancy\Models\Organization::class, OrganizationPolicy::class);

        // Enable Spatie Teams feature AFTER all providers have booted.
        // Using $this->app->booted() (Application-level) ensures this runs
        // after PermissionServiceProvider and all other providers.
        $this->app->booted(function (): void {
            config([
                'permission.teams'                               => true,
                'permission.column_names.team_foreign_key'       => 'organization_id',
            ]);

            try {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable) {
                // Cache table may not exist yet (e.g. after db:wipe before migrate)
            }
        });

        // Register middleware alias
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('current_organization', SetCurrentOrganization::class);
    }
}
