<?php

namespace Modules\Subscription\Observers;

use App\Foundation\BaseModelObserver;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscription\Models\SubscriptionChange;

class SubscriptionChangeObserver extends BaseModelObserver
{
    protected function module(): string       { return 'subscription'; }
    protected function resourceCode(): string { return 'subscription_change'; }

    protected function subjectLabel(Model $m): ?string
    {
        /** @var SubscriptionChange $m */
        return $m->change_type?->value . ' #' . $m->getKey();
    }

    protected function createdContext(Model $m): array
    {
        /** @var SubscriptionChange $m */
        return [
            'organization_id' => $m->organization_id,
            'from_plan_id'    => $m->from_plan_id,
            'to_plan_id'      => $m->to_plan_id,
            'change_type'     => $m->change_type?->value,
            'reason'          => $m->reason,
        ];
    }
}
