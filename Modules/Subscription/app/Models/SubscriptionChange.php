<?php

namespace Modules\Subscription\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Enums\ChangeType;

class SubscriptionChange extends Model
{
    use BelongsToOrganization;

    protected $table = 'subscription_changes';

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'from_plan_id',
        'to_plan_id',
        'changed_by',
        'change_type',
        'reason',
        'effective_at',
        'prorate_credit',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'change_type'    => ChangeType::class,
            'effective_at'   => 'datetime',
            'prorate_credit' => 'decimal:2',
            'created_at'     => 'datetime',
        ];
    }

    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }
}
