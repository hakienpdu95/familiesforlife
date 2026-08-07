<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §5.3/§5.4 — KHÔNG có rule cho
 * `framework_key` (không đổi được sau khi tạo — RegenerateGeneratedPromptAction luôn dùng
 * $prompt->framework_key hiện có, bỏ qua mọi giá trị request có thể gửi kèm).
 */
class UpdateGeneratedPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('prompt_framework_studio.use');
    }

    public function rules(): array
    {
        $prompt = $this->route('prompt');
        $framework = $prompt?->framework();

        $rules = [
            'label' => ['required', 'string', 'max:150'],
            'field_values' => ['required', 'array'],
        ];

        if ($framework) {
            foreach ($framework['fields'] as $field) {
                $rules["field_values.{$field['key']}"] = $field['required']
                    ? ['required', 'string', 'max:5000']
                    : ['nullable', 'string', 'max:5000'];
            }
        } else {
            // §5.4 — orphaned: không còn field definition để validate chi tiết theo từng khoá.
            // Đây KHÔNG phải "cho phép cập nhật" — Controller::edit() đã chặn truy cập route này
            // ở tầng UI, và RegenerateGeneratedPromptAction (qua RenderPromptFromFrameworkAction)
            // sẽ tự abort(422) nếu request vẫn lọt qua (VD gọi thẳng API, không qua UI) — lớp
            // validate này chỉ đảm bảo field_values.* có kiểu dữ liệu hợp lệ về mặt hình thức.
            $rules['field_values.*'] = ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }
}
