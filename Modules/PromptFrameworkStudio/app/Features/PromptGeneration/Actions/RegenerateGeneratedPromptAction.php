<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §5.3/§5.4 — luồng sửa/sinh lại. Framework
 * KHÔNG đổi được sau khi tạo — luôn dùng $prompt->framework_key hiện có, KHÔNG nhận từ input, để
 * 1 request cố tình gửi framework_key khác không thể đổi framework của bản ghi qua "sinh lại".
 *
 * Nếu $prompt->framework_key đã bị gỡ khỏi config (orphaned), RenderPromptFromFrameworkAction tự
 * abort(422) — Controller/edit() đã chặn trước ở UI (§5.4), đây là lớp phòng thủ thứ 2 cho trường
 * hợp Action bị gọi trực tiếp không qua route edit.
 */
class RegenerateGeneratedPromptAction
{
    use AsAction;

    public function __construct(private readonly RenderPromptFromFrameworkAction $renderPrompt) {}

    public function handle(GeneratedPrompt $prompt, string $label, array $fieldValues, int $updatedBy): GeneratedPrompt
    {
        $renderedPrompt = $this->renderPrompt->handle($prompt->framework_key, $fieldValues);

        $prompt->update([
            'label' => $label,
            'field_values' => $fieldValues,
            'rendered_prompt' => $renderedPrompt,
            'updated_by' => $updatedBy,
        ]);

        return $prompt->fresh();
    }
}
