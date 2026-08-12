<?php

namespace Modules\AIVideoStudioTemplate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §4.1 — `uuid` làm route key để nhất quán
 * quy ước toàn app (không lộ id tuần tự tăng dần qua API), dù hiện chưa có route web nào bind trực
 * tiếp theo shot (chỉ route API `shots/{shot}`, §6).
 */
class AiVideoStudioShot extends Model
{
    protected $table = 'ai_video_studio_shots';

    protected $fillable = [
        'uuid', 'project_id', 'sort_order', 'label',
        'subject', 'action', 'environment', 'camera', 'style', 'mood', 'duration_seconds', 'timeline_breakdown',
        'audio_direction', 'script_line', 'cta_text', 'constraints',
        'model_tool', 'reference_assets', 'compiled_prompt', 'image_prompt', 'motion_prompt',
        'ai_result', 'qc_notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_seconds' => 'integer',
    ];

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(AiVideoStudioProject::class, 'project_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
