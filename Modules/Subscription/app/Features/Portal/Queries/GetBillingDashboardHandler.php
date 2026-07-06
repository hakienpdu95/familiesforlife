<?php

namespace Modules\Subscription\Features\Portal\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\Models\Organization;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Models\SubscriptionChange;

class GetBillingDashboardHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var GetBillingDashboardQuery $query */
        $org = Organization::with(['planSubscriptions' => fn($q) => $q->with('plan.features')->latest('starts_at')])->findOrFail($query->organizationId);

        $sub = $org->planSubscription('main');

        $recentChanges = SubscriptionChange::where('organization_id', $org->id)
            ->with(['fromPlan:id,name,slug', 'toPlan:id,name,slug'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'organization'   => $org,
            'subscription'   => $sub,
            'plan'           => $sub?->plan,
            'is_active'      => $sub?->active() ?? false,
            'is_trial'       => $sub?->onTrial() ?? false,
            'is_canceled'    => $sub?->canceled() ?? false,
            'is_ended'       => $sub?->ended() ?? false,
            'ends_at'        => $sub?->ends_at,
            'trial_ends_at'  => $sub?->trial_ends_at,
            'canceled_at'    => $sub?->canceled_at,
            'recent_changes' => $recentChanges,
        ];
    }
}
