<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §4 (v3.0) — category dùng chung toàn nền tảng,
 * không extends TenantAwareModel nữa (organization_id vẫn còn cột, nhưng nullable/không còn
 * global-scope — xem migration 2026_07_13_000003). Biên tập viên phụ trách theo category qua
 * bảng post_category_editors (§4.2), không theo tổ chức.
 */
class PostCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'post_categories';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'uuid',
        'organization_id',
        'parent_id',
        'name',
        'slug',
        'description',
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
        return $this->belongsTo(PostCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PostCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(PostArticle::class, 'post_article_categories', 'category_id', 'article_id')
            ->withPivot('is_primary');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /** platform_section_editor được gán phụ trách category này (§4.2). */
    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'post_category_editors', 'post_category_id', 'user_id');
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
     * Cây danh mục gốc + con (active) — KHÔNG còn dùng cho nav/drawer nữa (từ
     * spec/Menu_Navigation_Technical_Specification.md Phase 3, nav đọc MenuItem::tree() qua
     * View Composer ở Modules/Menu). Vẫn được PublicCategoryController::index() dùng cho
     * home.blade.php (x-frontend.promo-bar/cta-band) — 2 section trang chủ độc lập với nav,
     * xem spec §2/§9.
     */
    public static function navTree(): \Illuminate\Support\Collection
    {
        return static::active()->root()
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }
}
