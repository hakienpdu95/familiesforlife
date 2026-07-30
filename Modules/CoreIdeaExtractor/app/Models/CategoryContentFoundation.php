<?php

namespace Modules\CoreIdeaExtractor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Post\Models\PostCategory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4)/§12.9 (N-N) — mô hình mới đầu tiên của module (trước đây
 * module hoàn toàn stateless). 1 bộ tiêu chí có thể áp dụng cho NHIỀU PostCategory qua bảng nối
 * cie_foundation_categories (unique post_category_id ở bảng nối — 1 category chỉ dùng ĐÚNG 1 bộ
 * tại 1 thời điểm, xem migration 2026_07_28_000001), tham chiếu 1 chiều sang Modules\Post — Post
 * KHÔNG biết/không cần đổi gì để hỗ trợ model này.
 */
class CategoryContentFoundation extends Model
{
    use LogsActivity;

    protected $table = 'cie_category_foundations';

    protected $fillable = [
        'core_focus',
        'writer_insights',
        'unique_angle',
        'content_goals',
        'pain_points',
        'rejected_ideas',
        'audience',
        'constraints',
        'style_sample',
        'created_by',
        'updated_by',
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
        return $this->belongsToMany(PostCategory::class, 'cie_foundation_categories', 'foundation_id', 'post_category_id')
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
