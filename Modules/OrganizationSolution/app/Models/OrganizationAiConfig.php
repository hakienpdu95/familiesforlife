<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationAiConfig extends TenantAwareModel
{
    protected $table = 'organization_ai_configs';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'ai_capability_code',
        'enabled', 'ai_agent_id', 'ai_prompt_id', 'provider', 'cost_limit',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'cost_limit' => 'decimal:2',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }
}
