<?php

namespace Modules\Lead\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Models\Lead;

class DeleteLeadAction
{
    use AsAction;

    public function handle(Lead $lead): void
    {
        $lead->delete();
    }
}
