<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** spec/PromptFrameworkStudio_Technical_Specification.md §5.1. */
class StoreGeneratedPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('prompt_framework_studio.use');
    }

    public function rules(): array
    {
        $frameworkKey = $this->input('framework_key');
        $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");

        $rules = [
            'framework_key' => ['required', 'string', Rule::in(array_keys(config('prompt_framework_studio.frameworks')))],
            // §4.4 (v2.7) — chuyên mục TUỲ CHỌN, nhận uuid (route key của PostCategory) chứ không
            // phải id, để không lộ id tự tăng ra HTML — cùng quy ước ContentOutlines.
            'post_category_uuid' => ['nullable', 'string', 'uuid', 'exists:post_categories,uuid'],
            'label' => ['required', 'string', 'max:150'],
            'field_values' => ['required', 'array'],
        ];

        if ($framework) {
            foreach ($framework['fields'] as $field) {
                $rules["field_values.{$field['key']}"] = $field['required']
                    ? ['required', 'string', 'max:5000']
                    : ['nullable', 'string', 'max:5000'];
            }
        }

        return $rules;
    }
}
