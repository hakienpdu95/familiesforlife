<?php

namespace Modules\ContentCalendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\ContentCalendar\Enums\CalendarEntryOrigin;
use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostCategory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.2 — KHÔNG extends TenantAwareModel (§4,
 * Lớp A — tài sản nền tảng, không thuộc Organization nào, cùng nguyên tắc PostCategory/PostArticle).
 */
class ContentCalendarEntry extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'content_calendar_entries';

    protected $fillable = [
        'post_category_id', 'title', 'brief', 'origin', 'origin_note',
        'status', 'target_publish_date', 'assigned_to', 'post_article_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'origin'               => CalendarEntryOrigin::class,
        'status'               => CalendarEntryStatus::class,
        'target_publish_date'  => 'date',
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
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function postArticle(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'post_article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * spec §5.2/§5.3.1 — khi đã liên kết bài viết thật, trạng thái HIỂN THỊ luôn ưu tiên đọc từ
     * PostArticleTranslation::status (nguồn sự thật duy nhất cho vòng đời xuất bản) — cột
     * `status` ở bảng này bị "đóng băng" tại thời điểm liên kết, chỉ còn ý nghĩa lịch sử. Tránh
     * tạo 2 nguồn sự thật cho 1 khái niệm.
     *
     * Đòi hỏi `postArticle.translations` đã được eager-load ở nơi gọi (ListCalendarEntriesAction,
     * §7.1) — PostArticle::mainTranslation() đọc qua property `translations` (đã eager-load thì
     * không re-query), tránh N+1/LazyLoadingViolationException.
     */
    public function displayStatusLabel(): string
    {
        if ($this->post_article_id && $this->relationLoaded('postArticle') && $this->postArticle) {
            $translation = $this->postArticle->mainTranslation();

            if ($translation) {
                return $translation->status->label();
            }
        }

        return $this->status->label();
    }

    /** true nếu đã liên kết 1 PostArticle thật — dùng để UI khoá badge trạng thái (§5.3.1). */
    public function isLinkedToArticle(): bool
    {
        return $this->post_article_id !== null;
    }
}
