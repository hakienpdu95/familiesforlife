<?php
namespace Modules\Customer\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Customer\Models\Customer;
use Modules\Customer\Observers\CustomerObserver;
use Modules\Customer\Policies\CustomerPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CustomerServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Customer';
    protected string $nameLower = 'customer';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path($this->name, 'config/customer.php'),
            'customer'
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Customer::class, CustomerPolicy::class);
        Customer::observe(CustomerObserver::class);
    }
}
