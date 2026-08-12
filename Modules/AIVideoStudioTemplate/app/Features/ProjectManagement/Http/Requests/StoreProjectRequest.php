<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §7 — tập giá trị lấy từ hằng số Model (§4.1, v1.7). */
class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai_video_studio_template.use');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'target_audience' => ['nullable', 'string'],
            'video_type' => ['nullable', 'string', Rule::in(array_keys(AiVideoStudioProject::VIDEO_TYPES))],
            'core_message' => ['nullable', 'string'],
            'aspect_ratio' => ['nullable', 'string', Rule::in(array_keys(AiVideoStudioProject::ASPECT_RATIOS))],
            'resolution' => ['nullable', 'string', Rule::in(array_keys(AiVideoStudioProject::RESOLUTIONS))],
            'default_subject' => ['nullable', 'string'],
            'reference_image_url' => ['nullable', 'string', 'max:2048'],
            'reference_context_prompt' => ['nullable', 'string', 'max:300'],
            'default_style' => ['nullable', 'string'],
            'default_constraints' => ['nullable', 'string'],
        ];
    }

    public function toData(): array
    {
        return [
            'name' => $this->input('name'),
            'description' => $this->input('description') ?: null,
            'objective' => $this->input('objective') ?: null,
            'targetAudience' => $this->input('target_audience') ?: null,
            'videoType' => $this->input('video_type') ?: null,
            'coreMessage' => $this->input('core_message') ?: null,
            'aspectRatio' => $this->input('aspect_ratio') ?: null,
            'resolution' => $this->input('resolution') ?: null,
            'defaultSubject' => $this->input('default_subject') ?: null,
            'referenceImageUrl' => $this->input('reference_image_url') ?: null,
            'referenceContextPrompt' => $this->input('reference_context_prompt') ?: null,
            'defaultStyle' => $this->input('default_style') ?: null,
            'defaultConstraints' => $this->input('default_constraints') ?: null,
        ];
    }
}
