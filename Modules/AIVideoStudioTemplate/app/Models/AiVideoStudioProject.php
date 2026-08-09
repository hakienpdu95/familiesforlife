<?php

namespace Modules\AIVideoStudioTemplate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §4.1 — KHÔNG TenantAwareModel, KHÔNG
 * soft-delete, KHÔNG LogsActivity — cùng lý do ContentOutline (không phải credential/tài sản
 * nghiệp vụ cần audit).
 */
class AiVideoStudioProject extends Model
{
    /**
     * Nguồn DUY NHẤT cho các tập giá trị cố định (v1.7).
     *
     * Trước v1.7 mỗi tập được viết lại ở 3 nơi (`_form.blade.php`, `StoreProjectRequest`,
     * `UpdateProjectRequest`) — thêm 1 lựa chọn ở form mà quên sửa validate sẽ ra lỗi 422 khó hiểu,
     * và trang `show`/tài liệu xuất in ra slug thô (`testimonial`) trong khi form hiển thị nhãn đẹp.
     * Dùng `array_keys()` cho `Rule::in()` và chính mảng này cho `<select>`/hiển thị.
     */
    public const VIDEO_TYPES = [
        'explainer' => 'Explainer (giải thích sản phẩm/dịch vụ)',
        'testimonial' => 'Testimonial (đánh giá/trải nghiệm thật)',
        'product_demo' => 'Product demo (trình diễn sản phẩm)',
        'storytelling' => 'Storytelling (kể chuyện thương hiệu)',
        'other' => 'Khác',
    ];

    public const ASPECT_RATIOS = [
        '16:9' => '16:9 — Ngang (YouTube)',
        '9:16' => '9:16 — Dọc (TikTok/Reels/Shorts)',
        '1:1' => '1:1 — Vuông',
        '4:5' => '4:5 — Instagram feed',
    ];

    public const RESOLUTIONS = [
        '720p' => '720p',
        '1080p' => '1080p — mạng xã hội',
        '2K' => '2K',
        '4K' => '4K — hero website',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Archived',
    ];

    protected $table = 'ai_video_studio_projects';

    protected $fillable = [
        'uuid', 'name', 'description',
        'objective', 'target_audience', 'video_type', 'core_message', 'aspect_ratio', 'resolution',
        'default_subject', 'reference_image_url', 'default_style', 'default_constraints',
        'status', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) Str::uuid();
            $model->status ??= 'draft';
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** Nhãn hiển thị của `video_type` — fallback về chính giá trị thô nếu dữ liệu cũ nằm ngoài tập. */
    public function videoTypeLabel(): ?string
    {
        return $this->video_type === null
            ? null
            : (self::VIDEO_TYPES[$this->video_type] ?? $this->video_type);
    }

    public function shots(): HasMany
    {
        return $this->hasMany(AiVideoStudioShot::class, 'project_id')->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
