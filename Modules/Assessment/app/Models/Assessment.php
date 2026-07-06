<?php

namespace Modules\Assessment\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Shared\Tenancy\OrganizationScope;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Support\LogOptions;

class Assessment extends TenantAwareModel
{
    protected $table = 'assessments';

    protected $fillable = [
        'organization_id',
        'assessment_code',
        'name',
        'version',
        'is_active',
        'has_scoring',
        'aggregation_model',
        'classification_type',
        'source_type',
        'source_ref',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('scoring');
    }

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'has_scoring' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'assessment_code';
    }

    /**
     * Override route binding để include global assessments (org_id NULL).
     * Trait BelongsToOrganization chỉ filter by org_id — bỏ sót global templates.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $isSuperAdmin = auth()->check() && auth()->user()->hasRole('super-admin');
        $orgId = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;

        $query = static::withoutGlobalScope(OrganizationScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value);

        if (! $isSuperAdmin && $orgId) {
            $query->where(function (Builder $q) use ($orgId): void {
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', $orgId);
            });
        }

        return $query->first();
    }

    /**
     * Scoring configs có thể là global (organization_id NULL — dùng chung cho mọi tổ chức,
     * seed sẵn như TDWCF/ORG_5PILLAR) hoặc riêng của một tổ chức. Bypass OrganizationScope
     * để tìm cả hai, ưu tiên bản ghi riêng của tổ chức hiện tại nếu có.
     */
    public static function findByCode(string $code): ?self
    {
        $orgId = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;

        return static::withoutGlobalScope(OrganizationScope::class)
            ->where('assessment_code', $code)
            ->where('is_active', true)
            ->where(function (Builder $q) use ($orgId): void {
                $q->whereNull('organization_id');
                if ($orgId !== null) {
                    $q->orWhere('organization_id', $orgId);
                }
            })
            ->orderByRaw('organization_id IS NULL')
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function usesWeightedDomain(): bool
    {
        return $this->aggregation_model === 'weighted_domain';
    }

    public function usesSectioned(): bool
    {
        return $this->aggregation_model === 'sectioned';
    }

    public function usesFlatSum(): bool
    {
        return $this->aggregation_model === 'flat_sum';
    }
}
