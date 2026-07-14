<?php

namespace Modules\Event\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Event\Enums\EventLocationType;
use Modules\Event\Enums\EventPriceType;
use Modules\Event\Enums\EventStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Sự kiện công khai — tài sản nền tảng (không organization_id), cùng lý do PostArticle
 * không còn organization_id (spec/Platform_RBAC_Phase2_Specification.md §3.3 v3.0): sự kiện
 * do độc giả ẩn danh nộp, không tổ chức nào sở hữu — xem spec/Event_Management_Technical_
 * Specification.md §3.2.
 */
class Event extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'events';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'uuid',
        'category_id',
        'title',
        'short_title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location_type',
        'venue_name',
        'venue_address',
        'province_code',
        'province_name',
        'ward_code',
        'ward_name',
        'full_address',
        'latitude',
        'longitude',
        'online_url',
        'website_url',
        'price_type',
        'price_amount',
        'price_min',
        'price_max',
        'poster_path',
        'poster_alt',
        'poster_width',
        'poster_height',
        'poster_size_bytes',
        'status',
        'is_featured',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'location_type'     => EventLocationType::class,
        'price_type'        => EventPriceType::class,
        'status'            => EventStatus::class,
        'is_featured'       => 'boolean',
        'price_amount'      => 'decimal:2',
        'price_min'         => 'decimal:2',
        'price_max'         => 'decimal:2',
        'latitude'          => 'decimal:7',
        'longitude'         => 'decimal:7',
        'poster_width'      => 'integer',
        'poster_height'     => 'integer',
        'poster_size_bytes' => 'integer',
        'approved_at'       => 'datetime',
        'rejected_at'       => 'datetime',
        'published_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /** Route key cho dashboard/events/{event} — route công khai dùng slug, resolve thủ công
     *  trong controller (không implicit binding), cùng lý do đã áp dụng cho PostArticleTranslation
     *  (spec/Event_Management_Technical_Specification.md §3.4). */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /** PII người nộp — KHÔNG bao giờ load ở query/route công khai (spec §5.3/§10.5). */
    public function submission(): HasOne
    {
        return $this->hasOne(EventSubmission::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
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

    public function scopePublished($query)
    {
        return $query->where('status', EventStatus::Published);
    }

    /** Sự kiện chưa kết thúc — dùng cho danh sách/hero công khai (Phase 3) và rule chống spam (§10.7). */
    public function scopeUpcoming($query)
    {
        return $query->where('end_date', '>=', now()->toDateString());
    }

    // ── Display helpers (Phase 3 — public views) ──────────────────────

    /** VND-only (spec §14 đã chốt, không đa tiền tệ) — dùng cho card/detail công khai. */
    public function priceLabel(): string
    {
        return match ($this->price_type) {
            \Modules\Event\Enums\EventPriceType::Free   => 'Miễn phí',
            \Modules\Event\Enums\EventPriceType::Single => number_format((float) $this->price_amount, 0, ',', '.').'đ',
            \Modules\Event\Enums\EventPriceType::Range  => number_format((float) $this->price_min, 0, ',', '.').'đ – '.number_format((float) $this->price_max, 0, ',', '.').'đ',
        };
    }

    public function locationLabel(): string
    {
        return $this->location_type === \Modules\Event\Enums\EventLocationType::Online
            ? 'Trực tuyến'
            : trim(($this->venue_name ?? '').($this->ward_name ? ", {$this->ward_name}" : '').($this->province_name ? ", {$this->province_name}" : ''), ', ');
    }
}
