<?php

namespace Modules\Task\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Models\TimeLog;

class DestroyTimeLogAction
{
    use AsAction;

    public function handle(TimeLog $log): void
    {
        $log->delete();
    }
}
