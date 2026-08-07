<?php

namespace Modules\PromptFrameworkStudio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
