<?php

namespace Modules\Subscription\Features\Cancel\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Subscription;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\ChangeType;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\Cancel\Events\SubscriptionResumed;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Models\SubscriptionChange;

class ResumeSubscriptionAction
{
    use AsAction;

    public function handle(Organization $org): Subscription
    {
        $sub = $org->planSubscription('main');

        if (!$sub || !$sub->canceled()) {
            throw new SubscriptionException('Không có subscription đã hủy để khôi phục.');
        }

        if ($sub->ended()) {
            throw new SubscriptionException('Subscription đã hết hạn, không thể khôi phục. Vui lòng đăng ký lại.');
        }

        DB::transaction(function () use ($sub, $org): void {
            // Clear canceled_at to resume — subscription remains active until ends_at
            $sub->fill(['canceled_at' => null])->save();

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $sub->id,
                'from_plan_id'    => $sub->plan_id,
                'to_plan_id'      => $sub->plan_id,
                'change_type'     => ChangeType::Resume,
                'effective_at'    => now(),
            ]);
        });

        SubscriptionContext::flush($org->id);
        SubscriptionResumed::dispatch($org, $sub->fresh());

        return $sub->fresh();
    }
}
