<?php

namespace Modules\Subscription\Features\Plans\Actions;

use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Plan;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Features\Plans\Data\PlanData;

class UpdatePlanAction
{
    use AsAction;

    public function handle(Plan $plan, PlanData $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update([
                'name'             => $data->name,
                'description'      => $data->description,
                'price'            => $data->price,
                'currency'         => $data->currency,
                'invoice_interval' => $data->invoice_interval,
                'invoice_period'   => $data->invoice_period,
                'trial_period'     => $data->trial_period,
                'trial_interval'   => $data->trial_interval,
                'grace_period'     => $data->grace_period,
                'grace_interval'   => $data->grace_interval,
                'is_active'        => $data->is_active,
            ]);

            $plan->forceFill([
                'tier'         => $data->tier,
                'is_public'    => $data->is_public,
                'annual_price' => $data->annual_price,
                'badge_color'  => $data->badge_color,
                'tag_line'     => $data->tag_line,
            ])->save();

            return $plan->fresh();
        });
    }
}
