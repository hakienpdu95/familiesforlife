<?php

namespace Modules\EntityComparison\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 2/§5 — tài sản nền tảng, platform-wide
 * (không organization_id) — cùng nhóm Post/Ocop/Event/Heritage, khác domain CRM/SaaS
 * (TenantAwareModel). Ảnh đại diện qua Media collection `cover` (§0 mục 3).
 */
class EntityType extends Model implements HasMedia
{
    use HasTenantMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'entity_types';

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'icon',
        'is_active', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }

    public function criteria(): BelongsToMany
    {
        return $this->belongsToMany(Criterion::class, 'entity_type_criterion')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
