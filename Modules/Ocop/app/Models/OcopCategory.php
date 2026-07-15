<?php

namespace Modules\Ocop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.4 — ngành hàng OCOP, tài sản nền tảng
 * (không organization_id), cùng nguyên tắc Banner/PostCategory. Phẳng, không cha/con (khác
 * PostCategory/EventCategory) — ERD §3.4 không có parent_id.
 */
class OcopCategory extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'ocop_categories';

    protected $fillable = [
        'uuid', 'name', 'slug', 'icon', 'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
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
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function products(): HasMany
    {
        return $this->hasMany(OcopProduct::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
