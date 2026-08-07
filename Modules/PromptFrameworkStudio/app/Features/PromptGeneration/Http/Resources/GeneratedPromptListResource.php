<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/** @mixin GeneratedPrompt */
class GeneratedPromptListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $framework = $this->framework(); // null = orphaned (§5.4)

        return [
            'uuid' => $this->uuid,
            'label' => $this->label,
            'framework_key' => $this->framework_key,
            'framework_name' => $framework['name'] ?? $this->framework_key,
            'is_orphaned' => $framework === null,
            // §4.4 (v2.7) — null = prompt không gắn chuyên mục (không có khối ngữ cảnh biên tập).
            'category_name' => $this->category?->name,
            'created_by_name' => $this->createdBy?->name,
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
            'show_url' => route('backend.promptstudio.prompts.show', $this->uuid),
            'edit_url' => $framework ? route('backend.promptstudio.prompts.edit', $this->uuid) : null,
            'delete_url' => route('backend.promptstudio.prompts.destroy', $this->uuid),
        ];
    }
}
