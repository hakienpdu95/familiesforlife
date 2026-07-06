<?php

namespace Modules\Subscription\Features\ChangePlan\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Plan;
use Laravelcm\Subscriptions\Models\Subscription;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\ChangeType;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\ChangePlan\Data\ChangePlanData;
use Modules\Subscription\Features\ChangePlan\Events\PlanChanged;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Models\SubscriptionChange;

class UpgradePlanAction
{
    use AsAction;

    public function handle(Organization $org, ChangePlanData $data): Subscription
    {
        $newPlan    = Plan::findOrFail($data->newPlanId);
        $currentSub = $org->planSubscription('main');

        if (!$currentSub || !$currentSub->active()) {
            throw new SubscriptionException('Không có subscription active để upgrade.');
        }

        if ($currentSub->plan_id === $newPlan->id) {
            throw new SubscriptionException('Tổ chức đã đang dùng plan này.');
        }

        $previousPlanId = $currentSub->plan_id;
        $credit         = $this->calcProrateCredit($currentSub);

        $subscription = DB::transaction(function () use ($currentSub, $newPlan, $previousPlanId, $credit, $data, $org): Subscription {
            $subscription = $currentSub->changePlan($newPlan);

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $subscription->id,
                'from_plan_id'    => $previousPlanId,
                'to_plan_id'      => $newPlan->id,
                'change_type'     => ChangeType::Upgrade,
                'reason'          => $data->reason,
                'effective_at'    => now(),
                'prorate_credit'  => $credit,
            ]);

            return $subscription;
        });

        SubscriptionContext::flush($org->id);
        PlanChanged::dispatch($org, $subscription, $previousPlanId, $newPlan->id);

        return $subscription;
    }

    private function calcProrateCredit(Subscription $sub): float
    {
        if (!$sub->ends_at || $sub->plan->isFree()) {
            return 0.0;
        }

        $daysRemaining = (int) now()->diffInDays($sub->ends_at, absolute: true);
        $totalDays     = (int) $sub->starts_at->diffInDays($sub->ends_at, absolute: true);

        return $totalDays > 0
            ? round(($sub->plan->price * $daysRemaining) / $totalDays, 2)
            : 0.0;
    }
}
