<?php

namespace Modules\Subscription\Features\Cancel\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Subscription;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\ChangeType;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\Cancel\Events\SubscriptionCanceled;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Models\SubscriptionChange;

class CancelSubscriptionAction
{
    use AsAction;

    public function handle(Organization $org, string $reason = ''): Subscription
    {
        $sub = $org->planSubscription('main');

        if (!$sub || $sub->canceled()) {
            throw new SubscriptionException('Không có subscription active để hủy.');
        }

        DB::transaction(function () use ($sub, $org, $reason): void {
            $sub->cancel();

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $sub->id,
                'from_plan_id'    => $sub->plan_id,
                'to_plan_id'      => $sub->plan_id,
                'change_type'     => ChangeType::Cancel,
                'reason'          => $reason,
                'effective_at'    => $sub->ends_at ?? now(),
            ]);
        });

        SubscriptionContext::flush($org->id);
        SubscriptionCanceled::dispatch($org, $sub->fresh());

        return $sub->fresh();
    }
}
