<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §7. */
class SaveShotAiResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai_video_studio_template.use');
    }

    public function rules(): array
    {
        return [
            'ai_result' => ['nullable', 'string'],
        ];
    }
}
