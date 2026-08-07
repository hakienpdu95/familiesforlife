<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §4.1 — ghép chuỗi thuần, KHÔNG gọi AI
 * Provider (§0) và KHÔNG dùng Blade template engine (tránh rủi ro injection cú pháp Blade từ dữ
 * liệu người dùng).
 *
 * v2.7 — ĐỔI TỪ `strtr($template)` SANG DỰNG KHỐI MARKDOWN. Bản cũ ghép 1 chuỗi phẳng
 * `"Context: {{context}}\nObjective: {{objective}}\n..."`, kéo theo 2 khuyết điểm thật:
 *   1. Field optional để trống vẫn in ra nhãn cụt (`Style: ` rỗng) — chính là mục "Tự động lược bỏ
 *      dòng nhãn trống" bị treo ở §7 "Ngoài phạm vi" từ v1.0; nhãn rỗng dạy model rằng khối đó
 *      không quan trọng, tệ hơn là không có nó.
 *   2. Không có chỗ nào chèn được ngữ cảnh biên tập/chuẩn nội dung — prompt sinh ra không có bất kỳ
 *      thông tin nào về độc giả thật của trang, dù dữ liệu đó đã có sẵn trong ContentFoundation.
 *
 * Cấu trúc mới theo "context sandwich" (spec/CoreIdeaExtractor.md §12.4), cùng khuôn
 * `ContentOutlines\...\BuildArticleDraftPromptAction`:
 *   TOP    — Bối cảnh biên tập (chuyên mục) → model hiểu ĐỘC GIẢ trước khi nhận yêu cầu.
 *   MIDDLE — các khối của framework, ĐÚNG THỨ TỰ CANON của framework đó (thứ tự chính là bản chất
 *            framework — KHÔNG sắp xếp lại), khối rỗng bị bỏ HẲN thay vì in nhãn cụt.
 *   BOTTOM — chuẩn nội dung nền tảng, đặt cuối vì đây là ràng buộc phải còn trong "tầm chú ý" của
 *            model ngay trước lúc nó bắt đầu sinh.
 *
 * `prompt_heading` của từng field lấy từ config, được suy ra từ CHÍNH chuỗi `template` cũ nên nhãn
 * giữ nguyên ngữ nghĩa gốc (vd `Narrowing/constraints`, không rút gọn thành nhãn UI `Narrowing`).
 * Field không có `prompt_heading` (hiện chỉ `freeform.text`) in nguyên văn, không bọc `## ` —
 * đúng bản chất "lưu lại nguyên văn 1 prompt đã có sẵn", không ép khuôn.
 *
 * `abort_if` bên dưới là nơi kiểm tra DUY NHẤT cho việc framework có tồn tại trong config hay
 * không (§4.1/§5.4) — CreateGeneratedPromptAction và RegenerateGeneratedPromptAction đều gọi
 * xuyên qua Action này để lấy rendered_prompt, nên cả 2 tự động thừa hưởng guard này.
 */
class RenderPromptFromFrameworkAction
{
    use AsAction;

    public function __construct(
        private readonly BuildEditorialContextBlockAction $buildEditorialContext,
        private readonly BuildFamilyValuesBlockAction $buildFamilyValues,
    ) {}

    /**
     * @param  array<string, string|null>  $fieldValues
     */
    public function handle(
        string $frameworkKey,
        array $fieldValues,
        ?CategoryContentFoundation $foundation = null,
        ?string $categoryName = null,
    ): string {
        $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");
        abort_if(! $framework, 422, 'Framework không tồn tại.');

        $blocks = [];

        // TOP — chỉ khi người dùng đã chọn chuyên mục CÓ ngữ cảnh biên tập đã soạn.
        $editorialContext = $this->buildEditorialContext->handle($foundation, $categoryName);
        if ($editorialContext !== '') {
            $blocks[] = $editorialContext;
        }

        // MIDDLE — khối framework theo đúng thứ tự canon, bỏ hẳn khối rỗng.
        foreach ($framework['fields'] as $field) {
            $value = trim((string) ($fieldValues[$field['key']] ?? ''));
            if ($value === '') {
                continue;
            }

            $heading = $field['prompt_heading'] ?? null;
            $blocks[] = $heading === null
                ? $value
                : "## {$heading}\n\n{$value}";
        }

        // BOTTOM — gắn với việc CÓ chuyên mục, không phải mặc định luôn chèn: đây là công cụ soạn
        // prompt đa dụng (xem BuildFamilyValuesBlockAction docblock).
        if ($foundation) {
            $familyValues = $this->buildFamilyValues->handle($foundation);
            if ($familyValues !== '') {
                $blocks[] = $familyValues;
            }
        }

        return implode("\n\n", $blocks);
    }

    /**
     * §4.1-style soft warning — đếm từ theo khoảng trắng Unicode (an toàn với tiếng Việt có dấu),
     * cùng công thức `ContentOutlines\...\BuildsSharedPromptBlocks::estimateWordCount()` để 2 module
     * báo cùng 1 con số cho cùng 1 đoạn văn.
     */
    public static function estimateWordCount(string $text): int
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $trimmed) ?: []);
    }
}
