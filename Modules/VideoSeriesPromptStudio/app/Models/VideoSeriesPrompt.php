<?php

namespace Modules\VideoSeriesPromptStudio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Post\Models\PostCategory;

/**
 * Cùng nhóm Modules\PromptFrameworkStudio\Models\GeneratedPrompt — chỉ lưu lại NGUYÊN VĂN prompt
 * đã ghép (BuildSeriesArchitecturePromptAction), KHÔNG gọi AI Provider trong app. KHÔNG
 * TenantAwareModel/organization_id — công cụ nội bộ đội content, không phải dữ liệu multi-tenant.
 */
class VideoSeriesPrompt extends Model
{
    protected $table = 'video_series_prompts';

    protected $fillable = [
        'uuid',
        'post_category_id',
        'label',
        'series_topic',
        'pov',
        'business_goal',
        'episode_count',
        'platform',
        'rendered_prompt',
        'created_by',
        'updated_by',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function platformLabel(): string
    {
        return config("video_series_prompt_studio.platform.options.{$this->platform}.label", $this->platform);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
