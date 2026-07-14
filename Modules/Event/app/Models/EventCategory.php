<?php

namespace Modules\Event\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Cây danh mục sự kiện — độc lập Modules\Post\Models\PostCategory (spec/Event_Management_
 * Technical_Specification.md §3.3): 2 domain nội dung khác nhau (bài viết vs sự kiện), trộn
 * chung sẽ buộc thay đổi 1 bên ảnh hưởng bên kia dù nhu cầu hiển thị khác nhau. Tài sản nền
 * tảng (không organization_id) — cùng lý do PostCategory không còn organization_id (v3.0).
 */
class EventCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'event_categories';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'uuid',
        'parent_id',
        'name',
        'slug',
        'icon',
        'color_hex',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EventCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category_id');
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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Cây danh mục gốc + con (active) dùng cho nav/dropdown chọn danh mục — cùng pattern
     * PostCategory::navTree().
     */
    public static function navTree(): Collection
    {
        return static::active()->root()
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }
}
