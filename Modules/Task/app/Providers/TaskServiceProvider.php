<?php

namespace Modules\Task\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Task\Console\Commands\OverdueTaskAlertCommand;
use Modules\Task\Models\Task;
use Modules\Task\Models\TimeLog;
use Modules\Task\Observers\TaskObserver;
use Modules\Task\Observers\TimeLogObserver;
use Modules\Task\Policies\TaskPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TaskServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Task';

    protected string $nameLower = 'task';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Task::class, TaskPolicy::class);

        Task::observe(TaskObserver::class);
        TimeLog::observe(TimeLogObserver::class);

        $this->commands([
            OverdueTaskAlertCommand::class,
        ]);
    }
}
