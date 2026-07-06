<?php

namespace Modules\Subscription\Features\Plans\Actions;

use Laravelcm\Subscriptions\Models\Plan;
use Lorisleiva\Actions\Concerns\AsAction;

class TogglePlanAction
{
    use AsAction;

    public function handle(Plan $plan): Plan
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return $plan->fresh();
    }
}
