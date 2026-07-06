<?php

namespace Modules\Task\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Task\Models\Task;

class TaskUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Task $task) {}
}
