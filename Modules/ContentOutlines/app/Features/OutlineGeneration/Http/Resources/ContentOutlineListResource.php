<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ContentOutlines\Features\OutlineGeneration\Actions\BuildContentOutlinePromptAction;
use Modules\ContentOutlines\Models\ContentOutline;

/** @mixin ContentOutline */
class ContentOutlineListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'label' => $this->label,
            'topic' => $this->topic,
            'target_keyword' => $this->target_keyword,
            'category_name' => $this->category?->name,
            'created_by_name' => $this->createdBy?->name,
            'linked_article_title' => $this->linkedArticle?->mainTranslation()?->title,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            // §4.1 (v1.1) — badge "dài" ở danh sách, cùng ngưỡng cảnh báo ở trang show.
            'is_long_prompt' => BuildContentOutlinePromptAction::estimateWordCount($this->generated_prompt) > BuildContentOutlinePromptAction::WORD_COUNT_WARNING_THRESHOLD,
            'show_url' => route('backend.contentoutlines.show', $this->uuid),
            'edit_url' => route('backend.contentoutlines.edit', $this->uuid),
            'delete_url' => route('backend.contentoutlines.destroy', $this->uuid),
        ];
    }
}
