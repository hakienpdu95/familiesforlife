<?php

namespace Modules\ContentFoundation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'rejected_ideas',
        'audience',
        'constraints',
        'style_sample',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'family_values_focus' => 'array',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
