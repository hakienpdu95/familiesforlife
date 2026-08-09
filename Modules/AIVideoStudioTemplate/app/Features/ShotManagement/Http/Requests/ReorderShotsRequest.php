<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §7 — ownership thật (thuộc đúng project)
 * chỉ kiểm tra được ở ReorderShotsAction (§3.4), KHÔNG kiểm tra được ở FormRequest thuần (cần query
 * DB theo {project} route param).
 */
class ReorderShotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai_video_studio_template.use');
    }

    public function rules(): array
    {
        return [
            'shot_ids' => ['required', 'array'],
            'shot_ids.*' => ['required', 'integer'],
        ];
    }
}
