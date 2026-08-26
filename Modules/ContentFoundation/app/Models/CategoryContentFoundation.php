<?php

namespace Modules\ContentFoundation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\Post\Models\PostCategory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/CoreIdeaExtractor.md §12 — tách từ Modules\CoreIdeaExtractor\Models\CategoryContentFoundation
 * để CoreIdeaExtractor và VideoIdeaExtractor cùng phụ thuộc 1 chiều vào module này thay vì phụ
 * thuộc chéo lẫn nhau. 1 bộ tiêu chí có thể áp dụng cho NHIỀU PostCategory qua bảng nối
 * content_foundation_categories (unique post_category_id — 1 category chỉ dùng ĐÚNG 1 bộ tại 1
 * thời điểm), tham chiếu 1 chiều sang Modules\Post — Post KHÔNG biết/không cần đổi gì để hỗ trợ
 * model này (cùng quy ước Ocop → Post).
 *
 * §12.13 — `product_service_docs`/`best_example_content` (martech.org/how-to-build-an-ai-content-
 * system-that-works) là 2 phần "Constants" còn thiếu so với mô hình AI content system chuẩn: tài
 * liệu mô tả chi tiết sản phẩm/dịch vụ, và ví dụ nội dung/dàn ý mẫu TỐT NHẤT đã có — khác
 * `style_sample` vốn chỉ là 1 đoạn văn mẫu ngắn về giọng văn, không phải 1 bài/dàn ý hoàn chỉnh.
 */
class CategoryContentFoundation extends Model
{
    use LogsActivity;

    protected $table = 'content_foundations';

    protected $fillable = [
        'core_focus',
        'writer_insights',
        'unique_angle',
        'content_goals',
        'pain_points',
        'objections',
        'decision_criteria',
        'family_values_focus',
        'family_conduct_focus',
        'rejected_ideas',
        'audience',
        'audience_behavior',
        'constraints',
        'style_sample',
        'product_service_docs',
        'best_example_content',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'family_values_focus' => 'array',
        'family_conduct_focus' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PostCategory::class, 'content_foundation_categories', 'foundation_id', 'post_category_id')
            ->withTimestamps();
    }

    /**
     * Toàn bộ field — dùng khi CHỈ 1 category cần hiển thị/chỉnh sửa đầy đủ (trang quản lý
     * ContentFoundation tải TẤT CẢ category theo dạng này để chuyển qua lại tức thì; CoreIdeaExtractor/
     * VideoIdeaExtractor chỉ fetch dạng này ĐÚNG 1 lần cho category vừa chọn — xem
     * ListCategoryFoundationsAction::handle() và CategoryFoundationController::show()).
     * Yêu cầu relation `categories` đã được eager-load sẵn ở caller.
     */
    public function toDetailArray(int $forCategoryId): array
    {
        return [
            'core_focus' => $this->core_focus,
            'writer_insights' => $this->writer_insights,
            'unique_angle' => $this->unique_angle,
            'content_goals' => $this->content_goals,
            'pain_points' => $this->pain_points,
            'objections' => $this->objections,
            'decision_criteria' => $this->decision_criteria,
            'family_values_focus' => $this->family_values_focus ?? [],
            'family_conduct_focus' => $this->family_conduct_focus ?? [],
            'rejected_ideas' => $this->rejected_ideas,
            'audience' => $this->audience,
            'audience_behavior' => $this->audience_behavior,
            'constraints' => $this->constraints,
            'style_sample' => $this->style_sample,
            'product_service_docs' => $this->product_service_docs,
            'best_example_content' => $this->best_example_content,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'shared_with' => $this->categories
                ->reject(fn (PostCategory $linked) => $linked->id === $forCategoryId)
                ->map(fn (PostCategory $linked) => ['uuid' => $linked->uuid, 'name' => $linked->name])
                ->values()
                ->all(),
        ];
    }

    /**
     * Bản RÚT GỌN chỉ 3 field CoreIdeaExtractor dùng làm "hint" khi liệt kê MỌI category ở Bước 0
     * (gọi tên chuyên mục phù hợp + cảnh báo ý đã từ chối của category KHÁC category đang chọn) —
     * xem index.blade.php::buildLayer2PromptText() `truncateForHint()`. Cắt ngắn NGAY TỪ SERVER
     * (không đợi client tự cắt) để không tải nguyên văn full_text (tới ~2000 ký tự/field) cho MỌI
     * category chỉ để hiển thị 160 ký tự đầu — đúng tinh thần "Select"/retrieval budget: không kéo
     * về nhiều hơn mức sẽ thực sự dùng. Không cần `categories` relation (không có shared_with).
     */
    public function toHintArray(int $maxHintChars = 160): array
    {
        return [
            'core_focus' => $this->core_focus ? Str::limit(trim($this->core_focus), $maxHintChars) : null,
            'unique_angle' => $this->unique_angle ? Str::limit(trim($this->unique_angle), $maxHintChars) : null,
            'rejected_ideas' => $this->rejected_ideas ? Str::limit(trim($this->rejected_ideas), $maxHintChars) : null,
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
