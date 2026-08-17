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
                $isCustomSelect = ($field['type'] ?? null) === 'select' && ! empty($field['allow_custom']);

                // spec/AIIdeaMatrixGenerator.md §2.6 (v2.2) — field select có `allow_custom` PHẢI
                // dùng `custom_max_length` (giới hạn NGẮN, mặc định 150 nếu field không tự khai)
                // thay vì `max:5000` mặc định của mọi field text/textarea — bản chất là 1 CỤM TỪ
                // NGẮN neo cảm xúc/văn hoá, không phải nội dung tự do dài. Không giới hạn này, người
                // dùng dán nguyên khối quảng cáo/thông cáo báo chí vào field vốn chỉ cần 1 câu ngắn
                // (xem ví dụ thật ở docblock RenderPromptFromFrameworkAction) — phá đúng mục đích
                // "Hằng số + Biến số" của cả mô hình.
                $maxLength = $isCustomSelect ? ($field['custom_max_length'] ?? 150) : 5000;

                $rule = $field['required']
                    ? ['required', 'string', "max:{$maxLength}"]
                    : ['nullable', 'string', "max:{$maxLength}"];

                // spec/AIIdeaMatrixGenerator.md §2.1 — field 'select' chỉ được nhận khoá có trong
                // chính options của nó, chặn ở đây TRƯỚC khi lưu vào field_values (lớp chính; lớp
                // phòng thủ thứ 2 là fallback `?? $value` ở RenderPromptFromFrameworkAction).
                // §2.5 (v2.1) — TRỪ field có `allow_custom`: tập giá trị của nó MỞ theo thiết kế
                // (biến số biên tập, options chỉ là gợi ý).
                if (($field['type'] ?? null) === 'select' && ! $isCustomSelect) {
                    $rule[] = Rule::in(array_keys($field['options']));
                }

                $rules["field_values.{$field['key']}"] = $rule;
            }
        }

        return $rules;
    }
}
