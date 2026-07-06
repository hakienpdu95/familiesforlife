<?php

namespace Modules\Subscription\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Console\Commands\ProcessExpiringSubscriptionsCommand;
use Modules\Subscription\Console\Commands\SendRenewalRemindersCommand;
use Modules\Subscription\Console\Commands\SubscriptionStatusCommand;
use Modules\Subscription\Features\Payment\Gateways\ManualGateway;
use Modules\Subscription\Features\Payment\Gateways\SePayGateway;
use Modules\Subscription\Features\Payment\Gateways\VNPayGateway;
use Modules\Subscription\Features\Payment\Support\PaymentGatewayManager;
use Modules\Subscription\Models\SubscriptionChange;
use Modules\Subscription\Models\SubscriptionInvoice;
use Modules\Subscription\Observers\SubscriptionChangeObserver;
use Modules\Subscription\Observers\SubscriptionInvoiceObserver;
use Modules\Subscription\Policies\SubscriptionPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SubscriptionServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Subscription';
    protected string $nameLower = 'subscription';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path($this->name, 'config/subscription.php'),
            'subscription'
        );

        $helpersPath = module_path($this->name, 'app/Helpers/subscription.php');
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }

        // Register payment gateway manager as singleton — adding a new gateway
        // is: implement PaymentGatewayInterface + add one register() call here.
        $this->app->singleton(PaymentGatewayManager::class, function (): PaymentGatewayManager {
            $manager = new PaymentGatewayManager();
            $manager->register(new ManualGateway());
            $manager->register(new VNPayGateway());
            $manager->register(new SePayGateway());
            return $manager;
        });
    }

    public function boot(): void
    {
        parent::boot();

        // Authorization
        Gate::policy(SubscriptionInvoice::class, SubscriptionPolicy::class);

        // Observers → ActivityLog
        SubscriptionInvoice::observe(SubscriptionInvoiceObserver::class);
        SubscriptionChange::observe(SubscriptionChangeObserver::class);

        // Blade directives
        Blade::if('canFeature', fn (string $slug) => org_can($slug));
        Blade::if('overLimit',  fn (string $slug, int $count) => org_at_limit($slug, $count));

        // Scheduled commands — not using Job/Queue
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('subscription:process-expiring')->dailyAt('00:05')->withoutOverlapping();
            $schedule->command('subscription:send-reminders')->dailyAt('08:00')->withoutOverlapping();
        });

        $this->commands([
            SubscriptionStatusCommand::class,
            ProcessExpiringSubscriptionsCommand::class,
            SendRenewalRemindersCommand::class,
        ]);

        // Flush the in-process SubscriptionContext cache after each queued job so that
        // long-lived workers don't carry stale subscription state across jobs.
        Queue::after(fn () => SubscriptionContext::flushAll());
    }
}
