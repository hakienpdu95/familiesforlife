<?php

namespace Modules\PromptFrameworkStudio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Post\Models\PostCategory;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §0/§3.2 — công cụ nội bộ đội content, KHÔNG
 * extends TenantAwareModel/organization_id, KHÔNG soft delete, KHÔNG LogsActivity — cùng nhóm
 * Modules\ContentOutlines\Models\ContentOutline (không phải credential/tài sản nghiệp vụ cần audit).
 */
class GeneratedPrompt extends Model
{
    protected $table = 'generated_prompts';

    protected $fillable = [
        'uuid',
        'framework_key',
        'post_category_id',
        'label',
        'field_values',
        'rendered_prompt',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'field_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * §5.4 — framework có thể đã bị gỡ khỏi config (orphaned); trả về null trong trường hợp đó,
     * KHÔNG throw. Dùng ở Controller/Resource để quyết định hiện view read-only hay form sửa.
     */
    public function framework(): ?array
    {
        return config("prompt_framework_studio.frameworks.{$this->framework_key}");
    }

    public function isOrphaned(): bool
    {
        return $this->framework() === null;
    }

    /**
     * §3.1 (v2.7) — chuyên mục dùng để tra ngữ cảnh biên tập lúc sinh prompt. Có thể null (không
     * chọn chuyên mục) hoặc trỏ tới chuyên mục ĐÃ BỊ XOÁ sau đó (nullOnDelete) — mọi nơi đọc quan
     * hệ này phải chịu được null, cùng cách xử lý framework orphaned (§5.4).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
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
