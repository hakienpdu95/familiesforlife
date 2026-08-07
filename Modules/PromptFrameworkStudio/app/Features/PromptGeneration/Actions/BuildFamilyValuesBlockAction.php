<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §4.5 (v2.7) — khối chuẩn nền tảng "Hệ giá
 * trị gia đình Việt Nam", cùng nội dung `ContentOutlines\Features\Concerns\BuildsSharedPromptBlocks
 * ::buildFamilyValuesBlock()` và quy ước CoreIdeaExtractor §12.10.
 *
 * Nguồn SỰ THẬT DUY NHẤT là `config('content_foundation.family_values')` — KHÔNG hardcode lặp lại
 * nội dung 4 giá trị ở đây (chúng được ban hành theo văn bản có số hiệu, sửa 1 chỗ phải đúng mọi
 * nơi). Viết riêng cho module này thay vì `use` trait của ContentOutlines: trait đó là chi tiết nội
 * bộ của module khác, phụ thuộc chéo vào nó sẽ khoá 2 module vào nhau; phụ thuộc chung vào config
 * của ContentFoundation thì đúng hướng (cả 3 module đều đã là consumer của ContentFoundation).
 *
 * KHÁC ContentOutlines/CoreIdeaExtractor ở ĐIỀU KIỆN CHÈN: 2 module kia luôn chèn (mọi nội dung
 * chúng sinh ra đều là nội dung gia đình). PromptFrameworkStudio là công cụ soạn prompt ĐA DỤNG —
 * người dùng có thể đang dịch 1 đoạn mô tả sản phẩm bằng RTF, chèn hệ giá trị gia đình vào đó là
 * nhiễu. Nên khối này chỉ chèn KHI người dùng đã chọn chuyên mục (tín hiệu rõ ràng rằng đây là
 * việc biên tập nội dung gia đình) — xem RenderPromptFromFrameworkAction.
 */
class BuildFamilyValuesBlockAction
{
    use AsAction;

    public function handle(?CategoryContentFoundation $foundation): string
    {
        $familyValues = config('content_foundation.family_values');
        $items = $familyValues['items'] ?? [];

        if ($items === []) {
            return '';
        }

        $lines = [
            '## Hệ giá trị gia đình Việt Nam (chuẩn nền tảng)',
            '',
            "Nội dung phải PHỤC VỤ (không đi ngược lại) 4 giá trị sau — {$familyValues['decision_ref']}:",
        ];

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
