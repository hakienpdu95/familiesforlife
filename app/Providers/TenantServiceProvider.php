<?php

namespace App\Providers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind TenantContext as a singleton so tests can swap it out
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());
    }

    public function boot(): void
    {
        // Flush TenantContext at the end of each queued job to prevent leaks
        Event::listen(\Illuminate\Queue\Events\JobProcessed::class, function (): void {
            TenantContext::flush();
        });

        Event::listen(\Illuminate\Queue\Events\JobFailed::class, function (): void {
            TenantContext::flush();
        });
    }
}
