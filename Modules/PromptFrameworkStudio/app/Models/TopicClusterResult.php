<?php

namespace Modules\PromptFrameworkStudio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — kết quả AI đã dán lại + phân tích
 * của 1 `GeneratedPrompt` dùng framework `topiccluster`. Xem docblock migration
 * `2026_08_28_000001_create_topic_cluster_results_table` cho lý do tách bảng riêng và cấu trúc
 * `structured`.
 */
class TopicClusterResult extends Model
{
    protected $table = 'topic_cluster_results';

    protected $fillable = [
        'generated_prompt_id',
        'ai_result_raw',
        'structured',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'structured' => 'array',
    ];

    public function generatedPrompt(): BelongsTo
    {
        return $this->belongsTo(GeneratedPrompt::class);
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
