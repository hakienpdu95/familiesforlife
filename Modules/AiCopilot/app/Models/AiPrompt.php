<?php

namespace Modules\AiCopilot\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPrompt extends TenantAwareModel
{
    protected $table = 'ai_prompts';

    protected $fillable = [
        'uuid', 'organization_id', 'agent_id', 'name', 'description',
        'system_prompt', 'user_template', 'variables_schema',
        'is_default', 'is_active', 'version', 'created_by',
    ];

    protected $casts = [
        'variables_schema' => 'array',
        'is_default'       => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'agent_id');
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $orgId = TenantContext::getOrganizationId();

        return static::withoutTenant()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->first();
    }
}
