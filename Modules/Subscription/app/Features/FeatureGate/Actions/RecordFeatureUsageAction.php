<?php

namespace Modules\Subscription\Features\FeatureGate\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Subscription;
use Lorisleiva\Actions\Concerns\AsAction;

class RecordFeatureUsageAction
{
    use AsAction;

    /**
     * Record usage for a quota-based feature (e.g. quota.ai_requests, quota.workflow_runs).
     * No-op if org has no active subscription or feature is not resettable.
     */
    public function handle(Organization $org, string $featureSlug, int $uses = 1): bool
    {
        /** @var Subscription|null $sub */
        $sub = $org->planSubscription('main');

        if (!$sub || !$sub->active()) {
            return false;
        }

        // Pessimistic lock on the subscription row serializes concurrent usage records
        // for the same subscription, preventing the vendor's read-modify-write from
        // being bypassed by two simultaneous requests (quota TOCTOU race).
        DB::transaction(function () use ($sub, $featureSlug, $uses): void {
            Subscription::lockForUpdate()->whereKey($sub->id)->first();
            $sub->recordFeatureUsage($featureSlug, $uses);
        });

        return true;
    }
}
