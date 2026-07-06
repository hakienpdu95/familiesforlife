<?php

namespace Modules\Recruitment\Models;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\PipelineStageType;

class RcPipelineStage extends Model
{
    protected $table = 'rc_pipeline_stages';

    protected $fillable = [
        'uuid',
        'org_id',
        'name',
        'stage_type',
        'sort_order',
        'require_score',
        'send_notification',
        'color_hex',
        'is_active',
    ];

    protected $casts = [
        'stage_type'        => PipelineStageType::class,
        'require_score'     => 'boolean',
        'send_notification' => 'boolean',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (TenantContext::isSet()) {
                $builder->where('rc_pipeline_stages.org_id', TenantContext::getOrganizationId());
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->org_id) && TenantContext::isSet()) {
                $model->org_id = TenantContext::getOrganizationId();
            }
        });
    }

    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    // ── Relationships ────────────────────────────────────────────────

    public function applications(): HasMany
    {
        return $this->hasMany(RcApplication::class, 'current_stage_id');
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(RcApplicationStageLog::class, 'stage_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('rc_pipeline_stages.is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('rc_pipeline_stages.sort_order');
    }
}
