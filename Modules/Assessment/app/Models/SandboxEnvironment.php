<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// organization_id nullable (null = global) — must NOT extend TenantAwareModel
class SandboxEnvironment extends Model
{
    protected $table = 'sandbox_environments';

    protected $fillable = [
        'uuid',
        'organization_id',
        'env_code',
        'name',
        'type',
        'tier',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'tier'       => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeAvailableForOrg(Builder $query, int $organizationId): Builder
    {
        return $query->where(function (Builder $q) use ($organizationId) {
            $q->whereNull('organization_id')
              ->orWhere('organization_id', $organizationId);
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SandboxTask::class, 'sandbox_env_id')->orderBy('sort_order');
    }
}
