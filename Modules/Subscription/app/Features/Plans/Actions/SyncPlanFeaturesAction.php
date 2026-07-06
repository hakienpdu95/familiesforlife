<?php

namespace Modules\Subscription\Features\Plans\Actions;

use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Models\Feature;
use Laravelcm\Subscriptions\Models\Plan;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Features\Plans\Data\PlanFeatureData;

class SyncPlanFeaturesAction
{
    use AsAction;

    /** @param array<PlanFeatureData> $features */
    public function handle(Plan $plan, array $features): void
    {
        DB::transaction(function () use ($plan, $features) {
            // Build the desired slug set
            $desiredSlugs = array_map(fn ($f) => $f->slug, $features);

            // Remove features that are no longer in the set
            if (!empty($desiredSlugs)) {
                Feature::where('plan_id', $plan->id)
                    ->whereNotIn('slug', $desiredSlugs)
                    ->delete();
            } else {
                Feature::where('plan_id', $plan->id)->delete();
            }

            // Upsert remaining features — no DELETE gap, concurrent reads always see
            // a complete (possibly momentarily stale) feature set for this plan.
            foreach ($features as $i => $f) {
                Feature::updateOrCreate(
                    ['plan_id' => $plan->id, 'slug' => $f->slug],
                    [
                        'name'                => $f->name,
                        'value'               => $f->value,
                        'resettable_period'   => $f->resettable_period ?? 0,
                        'resettable_interval' => $f->resettable_interval ?? 'month',
                        'sort_order'          => $i,
                    ]
                );
            }
        });
        // No cache to flush — SubscriptionContext reloads from DB on next request
    }
}
