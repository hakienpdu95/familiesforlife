<?php

namespace Modules\EntityComparison\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.2/§5 — đối tượng cụ thể (Trường A, Trường
 * B...). `meta` là escape hatch cho field vặt không cần filter/compare, không phải nơi lưu giá
 * trị tiêu chí (giá trị tiêu chí luôn qua criterionValues() + CriterionValueResolver).
 */
class Entity extends Model implements HasMedia
{
    use HasTenantMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'entities';

    protected $fillable = [
        'uuid', 'entity_type_id', 'name', 'slug', 'description',
        'is_active', 'sort_order', 'meta', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
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
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    public function criterionValues(): HasMany
    {
        return $this->hasMany(CriterionValue::class);
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

    public function scopeOfType(Builder $query, int $entityTypeId): void
    {
        $query->where('entity_type_id', $entityTypeId);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
