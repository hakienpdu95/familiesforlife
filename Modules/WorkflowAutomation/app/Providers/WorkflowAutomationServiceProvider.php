<?php

namespace Modules\WorkflowAutomation\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\WorkflowAutomation\Core\ActionRegistry;
use Modules\WorkflowAutomation\Core\ConditionEvaluator;
use Modules\WorkflowAutomation\Core\CooldownGuard;
use Modules\WorkflowAutomation\Core\SubjectRegistry;
use Modules\WorkflowAutomation\Core\TriggerRegistry;
use Modules\WorkflowAutomation\Executors\AiCallExecutor;
use Modules\WorkflowAutomation\Executors\CallWebhookExecutor;
use Modules\WorkflowAutomation\Executors\FlowLogExecutor;
use Modules\WorkflowAutomation\Executors\SendEmailExecutor;
use Modules\WorkflowAutomation\Executors\SendNotificationExecutor;
use Modules\WorkflowAutomation\Executors\SubjectStateSetExecutor;
use Modules\WorkflowAutomation\Executors\UpdateSubjectExecutor;
use Modules\WorkflowAutomation\Executors\UserTaskExecutor;
use Modules\WorkflowAutomation\Services\WorkflowEntityStateService;
use Modules\WorkflowAutomation\Listeners\WorkflowEventSubscriber;
use Modules\WorkflowAutomation\Triggers\GenericEventTrigger;
use Modules\WorkflowAutomation\Triggers\ManualTrigger;
use Nwidart\Modules\Support\ModuleServiceProvider;

class WorkflowAutomationServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'WorkflowAutomation';
    protected string $nameLower = 'workflowautomation';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart registers module config under the namespaced key
        // `workflowautomation.workflow_automation`. Re-expose it under the canonical
        // top-level `workflow_automation` key the module code reads from.
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/workflow_automation.php',
            'workflow_automation'
        );

        $this->app->singleton(TriggerRegistry::class);
        $this->app->singleton(ActionRegistry::class);
        $this->app->singleton(SubjectRegistry::class);
        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(CooldownGuard::class, fn ($app) =>
            new CooldownGuard($app->make(CacheRepository::class))
        );

        $this->app->singleton(WorkflowEntityStateService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $triggerRegistry = app(TriggerRegistry::class);
        $triggerRegistry->register(new ManualTrigger());

        // Declarative triggers — one GenericEventTrigger per config entry. Adding a new
        // trigger anywhere in the system is a config change, not a new class.
        foreach (config('workflow_automation.triggers', []) as $type => $def) {
            $triggerRegistry->register(new GenericEventTrigger($type, $def));
        }

        $actionRegistry = app(ActionRegistry::class);
        $actionRegistry->register(app(SendEmailExecutor::class));
        $actionRegistry->register(app(SendNotificationExecutor::class));
        $actionRegistry->register(app(UpdateSubjectExecutor::class));
        $actionRegistry->register(app(CallWebhookExecutor::class));
        // v2 executors
        $actionRegistry->register(app(AiCallExecutor::class));
        $actionRegistry->register(app(UserTaskExecutor::class));
        $actionRegistry->register(app(FlowLogExecutor::class));
        $actionRegistry->register(app(SubjectStateSetExecutor::class));

        // Bind every config trigger that declares an `event` to the application event bus.
        app('events')->subscribe(WorkflowEventSubscriber::class);
    }
}
