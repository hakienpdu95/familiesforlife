<?php

namespace Modules\EntityComparison\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\EntityComparison\Enums\CriterionType;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** spec/Entity_Comparison_Module_Technical_Spec.md §3.3/§5 — danh sách tiêu chí so sánh động. */
class Criterion extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'criteria';

    protected $fillable = [
        'uuid', 'name', 'slug', 'type', 'unit', 'description',
        'is_filterable', 'is_comparable', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'type' => CriterionType::class,
        'is_filterable' => 'boolean',
        'is_comparable' => 'boolean',
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

    /**
     * uuid, KHÔNG phải slug — khác EntityType (slug hợp lý vì có route public cần URL đẹp cho
     * SEO). Criterion chỉ có route admin (dashboard/entity-comparison/criteria/*), slug ở đây
     * không có giá trị SEO gì, chỉ lộ tên tiêu chí ra URL không cần thiết (VD tên gõ ẩu/trùng lặp
     * ra slug xấu). uuid đúng convention Entity đã dùng cho các model không cần URL công khai đẹp.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function options(): HasMany
    {
        return $this->hasMany(CriterionOption::class)->orderBy('sort_order');
    }

    public function entityTypes(): BelongsToMany
    {
        return $this->belongsToMany(EntityType::class, 'entity_type_criterion')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }

    public function values(): HasMany
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

    public function scopeFilterable(Builder $query): void
    {
        $query->where('is_filterable', true);
    }

    public function scopeComparable(Builder $query): void
    {
        $query->where('is_comparable', true);
    }
}
