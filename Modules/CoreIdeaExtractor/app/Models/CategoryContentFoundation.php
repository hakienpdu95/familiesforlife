<?php

namespace Modules\CoreIdeaExtractor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Post\Models\PostCategory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4) — mô hình mới đầu tiên của module (trước đây module hoàn
 * toàn stateless). 1 bản ghi / PostCategory (unique post_category_id), tham chiếu 1 chiều sang
 * Modules\Post — Post KHÔNG biết/không cần đổi gì để hỗ trợ model này.
 */
class CategoryContentFoundation extends Model
{
    use LogsActivity;

    protected $table = 'cie_category_foundations';

    protected $fillable = [
        'post_category_id',
        'core_focus',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
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
