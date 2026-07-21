<?php

namespace Modules\Page\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Features\PageManagement\PageTemplate;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §0/§2/§3 — trang tĩnh là tài sản nền
 * tảng, KHÔNG organization_id, không extend TenantAwareModel — cùng mô hình MenuItem/Banner.
 */
class Page extends Model implements HasMedia
{
    use SoftDeletes;
    use LogsActivity;
    use HasTenantMedia;

    protected $table = 'pages';

    protected $fillable = [
        'uuid', 'slug', 'title', 'template', 'content', 'excerpt',
        'status', 'published_at', 'is_system',
        'seo_title', 'seo_description', 'seo_noindex',
        'sort_order', 'view_count', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'        => PageStatus::class,
        'published_at'  => 'datetime',
        'is_system'     => 'boolean',
        'seo_noindex'   => 'boolean',
        'view_count'    => 'integer',
        'sort_order'    => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published)->whereNotNull('published_at');
    }

    /** Tiêu đề <title>/og:title — fallback về title khi seo_title trống. */
    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    /** Mô tả <meta description>/og:description — fallback về excerpt khi seo_description trống. */
    public function metaDescription(): ?string
    {
        return $this->seo_description ?: $this->excerpt;
    }

    /** Blade view thực render trang này — xem PageTemplate registry (§3.2.1). */
    public function resolveView(): string
    {
        return PageTemplate::viewFor($this->template);
    }
}
