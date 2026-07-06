<?php

namespace Modules\AiCopilot\Providers;

use Modules\AiCopilot\Services\AiDriverManager;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AiCopilotServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AiCopilot';

    protected string $nameLower = 'ai_copilot';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path($this->name, 'config/ai_copilot.php'),
            'ai_copilot'
        );

        $this->app->singleton(AiDriverManager::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
