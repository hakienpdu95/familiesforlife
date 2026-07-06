<?php

namespace Modules\AiCopilot\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMonthlyUsage extends Model
{
    use BelongsToOrganization;

    protected $table   = 'ai_monthly_usages';
    public $timestamps = false; // updated_at managed by DB ON UPDATE

    protected $fillable = [
        'organization_id', 'year_month', 'agent_id', 'task_type',
        'total_requests', 'successful_requests',
        'total_input_tokens', 'total_output_tokens', 'total_tokens',
        'total_cost_usd',
    ];

    public function scopeCurrentMonth(Builder $q): Builder
    {
        return $q->where('year_month', now()->format('Y-m'));
    }

    public function scopeAggregate(Builder $q): Builder
    {
        return $q->whereNull('agent_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }
}
