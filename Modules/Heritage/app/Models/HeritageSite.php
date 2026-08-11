<?php

namespace Modules\Heritage\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Heritage\Enums\HeritageRank;
use Modules\Heritage\Enums\HeritageSiteStatus;
use Modules\Heritage\Enums\HeritageType;
use Modules\Heritage\Enums\HeritageVisitingStatus;
use Modules\Post\Models\PostArticle;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Heritage_Technical_Specification.md §3.6 — di tích/di sản có cấu trúc, tài sản nền tảng
 * (không organization_id) — trục liên kết Post/Event/Ocop lại với nhau.
 *
 * CHỈ 1 relationship duy nhất trỏ RA module khác (articles()) — Heritage SỞ HỮU bảng pivot
 * post_article_heritage_sites, đúng lý do OcopProduct::articles() được chấp nhận là hard
 * dependency 1 chiều sang Post. KHÔNG có events()/ocopProducts() ở đây (xem §3.6 "Sửa sau
 * review") — nơi cần "các Event/OcopProduct thuộc di tích này" tự query trực tiếp ở tầng
 * controller (PublicHeritageController::show()), mirror pattern App\Models\Province không biết
 * Event/Ocop tồn tại.
 */
class HeritageSite extends Model implements HasMedia
{
    use HasTenantMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'heritage_sites';

    protected $fillable = [
        'uuid', 'name', 'slug', 'heritage_type', 'rank', 'era', 'description',
        'province_code', 'province_name', 'ward_code', 'ward_name', 'address',
        'latitude', 'longitude', 'visiting_status', 'status', 'is_featured', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'heritage_type' => HeritageType::class,
        'rank' => HeritageRank::class,
        'visiting_status' => HeritageVisitingStatus::class,
        'status' => HeritageSiteStatus::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_featured' => 'boolean',
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

    /** Route công khai dùng slug, resolve thủ công trong controller — cùng convention Event/OcopProduct. */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    /** Bài viết Post nhắc tới di tích này (many-to-many, tuỳ chọn). */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(PostArticle::class, 'post_article_heritage_sites', 'heritage_site_id', 'article_id');
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

    public function scopePublished(Builder $query): void
    {
        $query->where('status', HeritageSiteStatus::Published);
    }

    public function scopeForProvince(Builder $query, string $provinceCode): void
    {
        $query->where('province_code', $provinceCode);
    }
}
