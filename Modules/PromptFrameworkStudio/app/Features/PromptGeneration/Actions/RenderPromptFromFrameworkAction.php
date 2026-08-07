<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §4.1 — ghép chuỗi thuần (strtr), KHÔNG
 * dùng Blade template engine (không cần logic điều kiện, tránh rủi ro injection cú pháp Blade từ
 * dữ liệu người dùng). Field thiếu/để trống → thay bằng chuỗi rỗng, KHÔNG tự lược bỏ dòng nhãn
 * tương ứng (§2, để v1.1 kế tiếp nếu cần polish).
 *
 * `abort_if` bên dưới là nơi kiểm tra DUY NHẤT cho việc framework có tồn tại trong config hay
 * không (§4.1/§5.4) — CreateGeneratedPromptAction và RegenerateGeneratedPromptAction đều gọi
 * xuyên qua Action này để lấy rendered_prompt, nên cả 2 tự động thừa hưởng guard này (defense-in-
 * depth mà không lặp code kiểm tra ở từng nơi).
 */
class RenderPromptFromFrameworkAction
{
    use AsAction;

    public function handle(string $frameworkKey, array $fieldValues): string
    {
        $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");
        abort_if(! $framework, 422, 'Framework không tồn tại.');

        $replacements = [];
        foreach ($framework['fields'] as $field) {
            $replacements['{{'.$field['key'].'}}'] = trim((string) ($fieldValues[$field['key']] ?? ''));
        }

        return strtr($framework['template'], $replacements);
    }
}
