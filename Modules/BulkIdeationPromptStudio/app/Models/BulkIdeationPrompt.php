<?php

namespace Modules\BulkIdeationPromptStudio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Post\Models\PostCategory;

/**
 * Cùng nhóm Modules\PromptFrameworkStudio\Models\GeneratedPrompt và
 * Modules\VideoSeriesPromptStudio\Models\VideoSeriesPrompt — chỉ lưu lại NGUYÊN VĂN prompt đã
 * ghép (BuildBulkIdeationPromptAction), KHÔNG gọi AI Provider trong app. KHÔNG TenantAwareModel/
 * organization_id — công cụ nội bộ đội content, không phải dữ liệu multi-tenant.
 */
class BulkIdeationPrompt extends Model
{
    protected $table = 'bulk_ideation_prompts';

    protected $fillable = [
        'uuid',
        'post_category_id',
        'label',
        'raw_inputs',
        'business_goal',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
