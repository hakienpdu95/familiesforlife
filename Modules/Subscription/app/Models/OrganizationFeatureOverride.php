<?php

namespace Modules\Subscription\Models;

use App\Foundation\Models\TenantAwareModel;

class OrganizationFeatureOverride extends TenantAwareModel
{
    protected $table = 'organization_feature_overrides';

    protected $fillable = [
        'organization_id',
        'feature_slug',
        'value',
        'override_reason',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
