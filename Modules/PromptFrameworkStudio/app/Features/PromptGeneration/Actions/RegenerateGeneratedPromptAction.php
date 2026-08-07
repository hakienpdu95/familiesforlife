<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostCategory;
use Modules\PromptFrameworkStudio\Features\Concerns\ResolvesCategoryFoundation;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §5.3/§5.4 — luồng sửa/sinh lại. Framework
 * KHÔNG đổi được sau khi tạo — luôn dùng $prompt->framework_key hiện có, KHÔNG nhận từ input, để
 * 1 request cố tình gửi framework_key khác không thể đổi framework của bản ghi qua "sinh lại".
 *
 * KHÁC framework: chuyên mục ĐỔI ĐƯỢC khi sinh lại (§5.3 v2.7). Framework quyết định CẤU TRÚC bản
 * ghi (đổi thì `field_values` đã lưu thành vô nghĩa); chuyên mục chỉ là ngữ cảnh biên tập đắp thêm,
 * đổi/gỡ đều không làm hỏng dữ liệu đã có — và đây chính là đường nâng cấp cho các prompt tạo trước
 * v2.7 (chưa có chuyên mục nào) được gắn ngữ cảnh mà không phải tạo lại từ đầu.
 *
 * Ngữ cảnh biên tập được ĐỌC LẠI tại thời điểm sinh lại — prompt đã lưu KHÔNG tự đồng bộ khi ai đó
 * sửa Content Foundation của chuyên mục, phải bấm "Sinh lại" mới cập nhật (cùng quy ước
 * `RegenerateContentOutlinePromptAction`, tránh việc nội dung đã duyệt tự đổi sau lưng người dùng).
 *
 * Nếu $prompt->framework_key đã bị gỡ khỏi config (orphaned), RenderPromptFromFrameworkAction tự
 * abort(422) — Controller/edit() đã chặn trước ở UI (§5.4), đây là lớp phòng thủ thứ 2 cho trường
 * hợp Action bị gọi trực tiếp không qua route edit.
 */
class RegenerateGeneratedPromptAction
{
    use AsAction;
    use ResolvesCategoryFoundation;

    public function __construct(private readonly RenderPromptFromFrameworkAction $renderPrompt) {}

    /**
     * @param  array<string, string|null>  $fieldValues
     */
    public function handle(
        GeneratedPrompt $prompt,
        string $label,
        array $fieldValues,
        int $updatedBy,
        ?int $postCategoryId = null,
    ): GeneratedPrompt {
        $foundation = $this->resolveFoundation($postCategoryId);
        $categoryName = $postCategoryId ? PostCategory::find($postCategoryId)?->name : null;

        $renderedPrompt = $this->renderPrompt->handle(
            $prompt->framework_key,
            $fieldValues,
            $foundation,
            $categoryName,
        );

        $prompt->update([
            'post_category_id' => $postCategoryId,
            'label' => $label,
            'field_values' => $fieldValues,
            'rendered_prompt' => $renderedPrompt,
            'updated_by' => $updatedBy,
        ]);

        return $prompt->fresh();
    }
}
