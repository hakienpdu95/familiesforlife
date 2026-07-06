<?php

namespace Modules\Subscription\Features\Analytics\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\Models\Organization;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Enums\ChangeType;
use Modules\Subscription\Enums\InvoiceStatus;
use Modules\Subscription\Models\SubscriptionChange;
use Modules\Subscription\Models\SubscriptionInvoice;

class GetSubscriptionAnalyticsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var GetSubscriptionAnalyticsQuery $query */

        // MRR — sum of paid invoices in the target month (cross-org: withoutTenant)
        $mrr = SubscriptionInvoice::withoutTenant()
            ->where('status', InvoiceStatus::Paid)
            ->whereYear('paid_at', $query->year)
            ->whereMonth('paid_at', $query->month)
            ->sum('amount');

        // Plan distribution — active subscriptions grouped by plan
        $planDistribution = Plan::withCount([
            'subscriptions as active_count' => fn ($q) => $q
                ->whereNull('canceled_at')
                ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now())),
        ])->orderByDesc('active_count')->get(['id', 'name', 'slug', 'price']);

        // Churn — cancel events this month
        $churnCount = SubscriptionChange::withoutTenant()
            ->where('change_type', ChangeType::Cancel)
            ->whereYear('created_at', $query->year)
            ->whereMonth('created_at', $query->month)
            ->count();

        // New subscriptions this month
        $newCount = SubscriptionChange::withoutTenant()
            ->where('change_type', ChangeType::Subscribe)
            ->whereYear('created_at', $query->year)
            ->whereMonth('created_at', $query->month)
            ->count();

        // Total active orgs
        $activeOrgCount = Organization::withoutGlobalScopes()->count();

        return [
            'mrr'               => (float) $mrr,
            'plan_distribution' => $planDistribution,
            'churn_count'       => $churnCount,
            'new_count'         => $newCount,
            'active_org_count'  => $activeOrgCount,
            'period'            => sprintf('%04d-%02d', $query->year, $query->month),
        ];
    }
}
