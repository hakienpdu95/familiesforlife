<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Post\Models\PostCategory;

/**
 * spec/ContentOutlines_Technical_Specification.md §7.1 — cùng rules StoreContentOutlineRequest,
 * dùng khi sửa input rồi "Sinh lại" (RegenerateContentOutlinePromptAction).
 */
class UpdateContentOutlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('content_outlines.use');
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:200'],
            'topic' => ['required', 'string', 'max:300'],
            'target_keyword' => ['required', 'string', 'max:150'],
            'secondary_keywords' => ['nullable', 'string', 'max:500'],
            'search_intent' => ['nullable', Rule::in(['informational', 'commercial', 'transactional', 'navigational', 'comparison'])],
            'post_category_uuid' => ['nullable', 'string', 'uuid', 'exists:post_categories,uuid'],
            'target_audience' => ['nullable', 'string', 'max:500'],
            'content_goal' => ['nullable', 'string', 'max:2000'],
            'tone_style' => ['nullable', 'string', 'max:2000'],
            'competitor_urls' => ['nullable', 'string', 'max:2000'],
            'desired_word_count' => ['nullable', 'integer', 'min:100', 'max:20000'],
            'language' => ['nullable', Rule::in(['vi', 'en'])],
            'outline_depth' => ['nullable', Rule::in(['brief', 'standard', 'detailed'])],
            'content_role' => ['nullable', Rule::in(['pillar', 'cluster'])],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): array
    {
        $categoryId = null;
        if ($this->filled('post_category_uuid')) {
            $categoryId = PostCategory::where('uuid', $this->input('post_category_uuid'))->value('id');
        }

        return [
            'label' => $this->input('label') ?: null,
            'topic' => $this->input('topic'),
            'target_keyword' => $this->input('target_keyword'),
            'secondary_keywords' => $this->input('secondary_keywords') ?: null,
            'search_intent' => $this->input('search_intent') ?: null,
            'post_category_id' => $categoryId,
            'target_audience' => $this->input('target_audience') ?: null,
            'content_goal' => $this->input('content_goal') ?: null,
            'tone_style' => $this->input('tone_style') ?: null,
            'competitor_urls' => $this->input('competitor_urls') ?: null,
            'desired_word_count' => $this->input('desired_word_count') ?: null,
            'language' => $this->input('language') ?: 'vi',
            'outline_depth' => $this->input('outline_depth') ?: 'standard',
            'content_role' => $this->input('content_role') ?: null,
            'additional_notes' => $this->input('additional_notes') ?: null,
        ];
    }
}
