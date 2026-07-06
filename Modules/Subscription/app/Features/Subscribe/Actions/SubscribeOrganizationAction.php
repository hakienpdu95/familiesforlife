<?php

namespace Modules\Subscription\Features\Subscribe\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Plan;
use Laravelcm\Subscriptions\Models\Subscription;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\ChangeType;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Features\Subscribe\Data\SubscribeData;
use Modules\Subscription\Features\Subscribe\Events\SubscriptionCreated;
use Modules\Subscription\Models\SubscriptionChange;

class SubscribeOrganizationAction
{
    use AsAction;

    /**
     * Gán plan mặc định (config('subscription.default_plan')) cho 1 tổ chức — dùng cho mọi
     * nơi tạo Organization mà không đi qua StoreOrganizationAction/event OrganizationCreated
     * (vd. tạo tổ chức đích khi tạo Deployment Target). Trả null nếu chưa cấu hình plan mặc
     * định nào đang active — im lặng bỏ qua, giống hệt AutoSubscribeOnOrgCreated, để 2 nơi
     * gọi cùng 1 hành vi, không lệch nhau.
     */
    public static function subscribeToDefaultPlan(Organization $org): ?Subscription
    {
        $plan = Plan::where('slug', config('subscription.default_plan', 'starter'))
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return null;
        }

        return static::run($org, new SubscribeData(
            planId: $plan->id,
            idempotentKey: 'auto-' . $org->id,
        ));
    }

    public function handle(Organization $org, SubscribeData $data): Subscription
    {
        // Idempotency check — exact match on slug to avoid false positives
        if ($data->idempotentKey) {
            $existing = $org->planSubscriptions()
                ->where('slug', $data->idempotentKey)
                ->first();
            if ($existing) return $existing;
        }

        $plan = Plan::findOrFail($data->planId);

        $subscription = DB::transaction(function () use ($org, $plan, $data): Subscription {
            $current = $org->planSubscription('main');
            if ($current && $current->active()) {
                $current->cancel();
            }

            $subscription = $org->newPlanSubscription(
                $data->slug ?? 'main',
                $plan,
                $data->startDate
            );

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $subscription->id,
                'from_plan_id'    => $current?->plan_id,
                'to_plan_id'      => $plan->id,
                'change_type'     => ChangeType::Subscribe,
                'effective_at'    => now(),
            ]);

            return $subscription;
        });

        SubscriptionContext::flush($org->id);

        SubscriptionCreated::dispatch($org, $subscription, $plan);

        return $subscription;
    }
}
