<?php

namespace Modules\AiCopilot\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends TenantAwareModel
{
    protected $table = 'ai_agents';

    protected $fillable = [
        'uuid', 'organization_id', 'name', 'slug', 'description',
        'task_type', 'provider', 'model', 'temperature', 'max_tokens',
        'timeout_seconds', 'sync_mode', 'is_active', 'is_system', 'created_by',
    ];

    protected $casts = [
        'temperature' => 'float',
        'sync_mode'   => 'boolean',
        'is_active'   => 'boolean',
        'is_system'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn (Builder $q) => $q->where('is_active', true));
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(AiPrompt::class, 'agent_id');
    }

    public function defaultPrompt(): AiPrompt
    {
        return $this->prompts()
            ->where('is_default', true)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AiRequest::class, 'agent_id');
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $orgId = TenantContext::getOrganizationId();

        return static::withoutTenant()
            ->withoutGlobalScope('active')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->first();
    }

    /**
     * Tìm agent theo slug — org-specific wins, fallback system agent (org_id=NULL).
     *
     * Bypass OrganizationScope (withoutTenant) để tìm system agents, và scope 'active'.
     */
    public static function findBySlug(string $slug, ?int $orgId): self
    {
        return static::withoutTenant()
            ->withoutGlobalScope('active')
            ->where('slug', $slug)
            ->where(function (Builder $q) use ($orgId) {
                $q->where('organization_id', $orgId)
                  ->orWhereNull('organization_id');
            })
            ->orderByRaw('organization_id IS NULL ASC') // 0=org-specific (wins), 1=system (fallback)
            ->firstOrFail();
    }
}
