<?php

namespace Modules\ContentOutlines\Features\Concerns;

use Modules\ContentFoundation\Models\CategoryContentFoundation;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.17 (v1.14) — tách ra từ
 * `BuildContentOutlinePromptAction` khi `BuildArticleDraftPromptAction` (Feature `ArticleDrafting`)
 * xuất hiện là ĐIỂM DÙNG THỨ 2 cho `estimateWordCount()`/`buildFamilyValuesBlock()` — cùng nguyên
 * tắc "extract khi có điểm dùng thứ 2" đã áp dụng cho `ResolvesCategoryContext` (§4.6, v1.2).
 * KHÔNG đổi hành vi so với bản gốc trong `BuildContentOutlinePromptAction` trước v1.14.
 */
trait BuildsSharedPromptBlocks
{
    /**
     * §4.1 (v1.1) — đếm từ theo khoảng trắng Unicode (an toàn với tiếng Việt có dấu). Dùng
     * static::estimateWordCount() hoặc gọi qua tên lớp cụ thể (VD
     * `BuildContentOutlinePromptAction::estimateWordCount()`) — cả 2 lớp dùng trait này đều gọi
     * được, hành vi giống nhau tuyệt đối (không override).
     */
    public static function estimateWordCount(string $text): int
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $trimmed) ?: []);
    }

    /**
     * spec/ContentOutlines_Technical_Specification.md §3.2 — khối "Hệ giá trị gia đình Việt Nam"
     * CỐ ĐỊNH, LUÔN chèn bất kể có chọn category hay không — đúng quy ước CoreIdeaExtractor
     * §12.10. Nguồn SỰ THẬT DUY NHẤT là config('content_foundation.family_values'), KHÔNG hardcode
     * lặp lại nội dung ở đây. Dùng ở CẢ prompt outline (BuildContentOutlinePromptAction) và prompt
     * viết bài (BuildArticleDraftPromptAction, §4.17 v1.14) — bài viết THẬT được viết ra là nơi
     * rủi ro vi phạm giá trị gia đình cao nhất, không kém gì lúc dựng outline.
     */
    private function buildFamilyValuesBlock(?CategoryContentFoundation $foundation): string
    {
        $familyValues = config('content_foundation.family_values');
        $items = $familyValues['items'] ?? [];

        if ($items === []) {
            return '';
        }

        $lines = [];
        $lines[] = '## Hệ giá trị gia đình Việt Nam (chuẩn nền tảng)';
        $lines[] = "Nội dung phải PHỤC VỤ (không đi ngược lại) 4 giá trị sau — {$familyValues['decision_ref']}:";

        foreach ($items as $item) {
            $lines[] = "- **{$item['label']}:** {$item['description']}";
        }

        $lines[] = 'KHÔNG cổ suý bất bình đẳng giới, bạo lực gia đình, hủ tục lạc hậu, hoặc lối sống thiếu chuẩn mực.';

        $focusKeys = $foundation?->family_values_focus ?? [];
        if ($focusKeys !== []) {
            $focusLabels = collect($items)
                ->whereIn('key', $focusKeys)
                ->pluck('label')
                ->implode(', ');

            if ($focusLabels !== '') {
                $lines[] = "**Ưu tiên bổ sung cho chuyên mục này:** {$focusLabels}.";
            }
        }

        return implode("\n", $lines);
    }
}
