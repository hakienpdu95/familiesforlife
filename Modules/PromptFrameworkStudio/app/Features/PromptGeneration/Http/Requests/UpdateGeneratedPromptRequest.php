<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // §5.3 (v2.7) — KHÁC framework_key: chuyên mục đổi/gỡ được khi sinh lại (chỉ là ngữ
            // cảnh đắp thêm, không quyết định cấu trúc bản ghi).
            'post_category_uuid' => ['nullable', 'string', 'uuid', 'exists:post_categories,uuid'],
            'label' => ['required', 'string', 'max:150'],
            'field_values' => ['required', 'array'],
        ];

        if ($framework) {
            foreach ($framework['fields'] as $field) {
                $isCustomSelect = ($field['type'] ?? null) === 'select' && ! empty($field['allow_custom']);

                // spec/AIIdeaMatrixGenerator.md §2.6 (v2.2) — cùng lý do StoreGeneratedPromptRequest.
                $maxLength = $isCustomSelect ? ($field['custom_max_length'] ?? 150) : 5000;

                $rule = $field['required']
                    ? ['required', 'string', "max:{$maxLength}"]
                    : ['nullable', 'string', "max:{$maxLength}"];

                // spec/AIIdeaMatrixGenerator.md §2.1/§2.5 — cùng lý do StoreGeneratedPromptRequest
                // (Rule::in chỉ cho tập đóng; field `allow_custom` là tập mở, nhận text tự do).
                if (($field['type'] ?? null) === 'select' && ! $isCustomSelect) {
                    $rule[] = Rule::in(array_keys($field['options']));
                }

                $rules["field_values.{$field['key']}"] = $rule;
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
