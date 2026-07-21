<?php

namespace Modules\Post\Features\VersionHistory\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticleVersion;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §12 — v2 (2026-07-21, phản hồi người dùng:
 * "không có chi tiết đoạn nào thêm mới/xoá bỏ, không highlight thì theo dõi sao được"):
 *
 * - Block diff giờ dùng LCS (Longest Common Subsequence, DP O(m×n) trên danh sách block — số
 *   block/bài thường vài chục nên không đáng lo hiệu năng) thay vì so theo VỊ TRÍ như v1. Sửa
 *   đúng bug đã biết ở v1: chèn/xoá 1 block ở giữa không còn làm lệch chỉ số các block phía sau
 *   (trước đây bị gắn nhầm nhãn "Thay đổi" thay vì "Thêm mới"/"Xoá bớt").
 * - Với khối văn bản "Thay đổi", thêm `diff_html` — highlight TỪNG TỪ khác nhau ngay trong 1 đoạn
 *   (không chỉ hiện 2 khối cũ/mới cạnh nhau như v1) — xem `textWordDiff()`.
 *
 * Giới hạn còn lại (chấp nhận được ở v2): khi 1 cụm gồm NHIỀU block bị thay cùng lúc (vd 2 đoạn cũ
 * bị thay bằng 3 đoạn mới), việc ghép cặp "block nào coi là sửa từ block nào" trong `groupOps()`
 * vẫn theo thứ tự xuất hiện trong cụm (không LCS đệ quy lồng nhau) — trường hợp phổ biến nhất
 * (sửa/thêm/xoá 1 block riêng lẻ) đã cho kết quả chính xác.
 */
class CompareArticleVersionsAction
{
    use AsAction;

    private const SCALAR_FIELDS = [
        'title', 'slug', 'excerpt', 'seo_title', 'seo_description',
        'disclosure_text', 'cta_text', 'cta_url',
    ];

    /** An toàn hiệu năng cho diff từng từ (DP O(n×m)) — vượt ngưỡng thì rơi về hiện nguyên khối. */
    private const MAX_WORD_DIFF_CELLS = 200_000;

    public function handle(PostArticleVersion $from, PostArticleVersion $to): array
    {
        return [
            'from'           => ['id' => $from->id, 'version_number' => $from->version_number],
            'to'             => ['id' => $to->id, 'version_number' => $to->version_number],
            'field_changes'  => $this->diffFields($from->snapshot['translation'] ?? [], $to->snapshot['translation'] ?? []),
            'block_changes'  => $this->diffBlocks($from->snapshot['blocks'] ?? [], $to->snapshot['blocks'] ?? []),
        ];
    }

    private function diffFields(array $before, array $after): array
    {
        $changes = [];

        foreach (self::SCALAR_FIELDS as $field) {
            $beforeValue = $before[$field] ?? null;
            $afterValue  = $after[$field] ?? null;

            if ($beforeValue !== $afterValue) {
                $changes[] = ['field' => $field, 'before' => $beforeValue, 'after' => $afterValue];
            }
        }

        return $changes;
    }

    private function diffBlocks(array $before, array $after): array
    {
        return $this->groupOps($this->computeBlockOps($before, $after), $before, $after);
    }

    /**
     * LCS chuẩn qua bảng quy hoạch động — trả về chuỗi thao tác 'equal'/'delete'/'insert' theo
     * đúng thứ tự cần áp dụng để biến `$before` thành `$after`.
     *
     * @return list<array{op: string, before_index: ?int, after_index: ?int}>
     */
    private function computeBlockOps(array $before, array $after): array
    {
        $m = count($before);
        $n = count($after);

        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                $dp[$i][$j] = $this->blocksEqual($before[$i], $after[$j])
                    ? $dp[$i + 1][$j + 1] + 1
                    : max($dp[$i + 1][$j], $dp[$i][$j + 1]);
            }
        }

        $ops = [];
        $i = 0;
        $j = 0;
        while ($i < $m && $j < $n) {
            if ($this->blocksEqual($before[$i], $after[$j])) {
                $ops[] = ['op' => 'equal', 'before_index' => $i, 'after_index' => $j];
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $ops[] = ['op' => 'delete', 'before_index' => $i, 'after_index' => null];
                $i++;
            } else {
                $ops[] = ['op' => 'insert', 'before_index' => null, 'after_index' => $j];
                $j++;
            }
        }
        while ($i < $m) {
            $ops[] = ['op' => 'delete', 'before_index' => $i, 'after_index' => null];
            $i++;
        }
        while ($j < $n) {
            $ops[] = ['op' => 'insert', 'before_index' => null, 'after_index' => $j];
            $j++;
        }

        return $ops;
    }

    /**
     * Gom chuỗi thao tác LCS thành danh sách hiển thị: 'unchanged' cho block khớp; mỗi cụm
     * delete-rồi-insert liên tiếp (block bị thay ở cùng 1 vị trí) ghép cặp theo thứ tự thành
     * 'changed', phần dư (nếu 2 bên khác số lượng) thành 'removed'/'added' thuần.
     */
    private function groupOps(array $ops, array $before, array $after): array
    {
        $changes = [];
        $outputIndex = 0;
        $i = 0;
        $total = count($ops);

        while ($i < $total) {
            if ($ops[$i]['op'] === 'equal') {
                $block = $before[$ops[$i]['before_index']];
                $changes[] = ['index' => $outputIndex++, 'status' => 'unchanged', 'type' => $block['type']];
                $i++;
                continue;
            }

            $deleteIndexes = [];
            while ($i < $total && $ops[$i]['op'] === 'delete') {
                $deleteIndexes[] = $ops[$i]['before_index'];
                $i++;
            }
            $insertIndexes = [];
            while ($i < $total && $ops[$i]['op'] === 'insert') {
                $insertIndexes[] = $ops[$i]['after_index'];
                $i++;
            }

            $pairCount = min(count($deleteIndexes), count($insertIndexes));
            for ($k = 0; $k < $pairCount; $k++) {
                $beforeBlock = $before[$deleteIndexes[$k]];
                $afterBlock  = $after[$insertIndexes[$k]];
                $changes[] = [
                    'index'  => $outputIndex++,
                    'status' => 'changed',
                    'type'   => $afterBlock['type'],
                    ...$this->blockPayload($beforeBlock, 'before'),
                    ...$this->blockPayload($afterBlock, 'after'),
                    ...$this->productSummary($beforeBlock, $afterBlock),
                    ...$this->textWordDiff($beforeBlock, $afterBlock),
                ];
            }
            for ($k = $pairCount; $k < count($deleteIndexes); $k++) {
                $beforeBlock = $before[$deleteIndexes[$k]];
                $changes[] = ['index' => $outputIndex++, 'status' => 'removed', 'type' => $beforeBlock['type'], ...$this->blockPayload($beforeBlock, 'before')];
            }
            for ($k = $pairCount; $k < count($insertIndexes); $k++) {
                $afterBlock = $after[$insertIndexes[$k]];
                $changes[] = ['index' => $outputIndex++, 'status' => 'added', 'type' => $afterBlock['type'], ...$this->blockPayload($afterBlock, 'after')];
            }
        }

        return $changes;
    }

    /** so `text_html` sau strip_tags+trim; product: so toàn bộ cấu hình. */
    private function blocksEqual(array $before, array $after): bool
    {
        if (($before['type'] ?? null) !== ($after['type'] ?? null)) {
            return false;
        }

        if ($before['type'] === 'text') {
            return trim(strip_tags($before['text_html'] ?? '')) === trim(strip_tags($after['text_html'] ?? ''));
        }

        return $before === $after;
    }

    private function blockPayload(array $block, string $prefix): array
    {
        if ($block['type'] === 'text') {
            return [$prefix . '_html' => $block['text_html'] ?? ''];
        }

        return [
            $prefix . '_template'    => $block['template'] ?? null,
            $prefix . '_heading'     => $block['heading'] ?? null,
            $prefix . '_product_ids' => collect($block['items'] ?? [])->pluck('product_id')->all(),
        ];
    }

    /** với block product bị "Thay đổi", tóm tắt template + array_diff danh sách product_id. */
    private function productSummary(array $before, array $after): array
    {
        if ($before['type'] !== 'product' || $after['type'] !== 'product') {
            return [];
        }

        $beforeIds = collect($before['items'] ?? [])->pluck('product_id')->all();
        $afterIds  = collect($after['items'] ?? [])->pluck('product_id')->all();

        return [
            'product_ids_added'   => array_values(array_diff($afterIds, $beforeIds)),
            'product_ids_removed' => array_values(array_diff($beforeIds, $afterIds)),
        ];
    }

    /**
     * Highlight từng từ khác nhau trong 1 khối văn bản bị "Thay đổi" — LCS ở mức TỪ (không phải
     * cả khối) để người dùng thấy chính xác cụm từ nào thêm/bớt, thay vì phải tự so 2 khối cũ/mới
     * cạnh nhau. Bỏ qua (rơi về before_html/after_html cũ) nếu đoạn quá dài — an toàn hiệu năng.
     */
    private function textWordDiff(array $before, array $after): array
    {
        if ($before['type'] !== 'text' || $after['type'] !== 'text') {
            return [];
        }

        $beforeWords = $this->splitWords($before['text_html'] ?? '');
        $afterWords  = $this->splitWords($after['text_html'] ?? '');

        if (count($beforeWords) * count($afterWords) > self::MAX_WORD_DIFF_CELLS) {
            return [];
        }

        return ['diff_html' => $this->renderWordDiffHtml($beforeWords, $afterWords)];
    }

    /**
     * KHÔNG dùng strip_tags() trực tiếp — 2 thẻ khối cạnh nhau không có khoảng trắng giữa
     * (`</p><p>`, `</li><li>`, mẫu HTML thật do trình soạn thảo sinh ra, đã verify trên DB thật)
     * khiến strip_tags() nối liền chữ cuối đoạn trước với chữ đầu đoạn sau thành 1 "từ" giả —
     * làm sai lệch việc so khớp từ. Thay mỗi thẻ bằng 1 khoảng trắng trước khi tách từ, rồi gộp
     * khoảng trắng thừa lại — không thể làm dính từ, chỉ có thể thừa khoảng trắng vô hại.
     */
    /**
     * Tách riêng dấu câu khỏi từ (không gộp chung 1 token "từ+dấu câu") — bug thật đã verify:
     * nếu "chung." (có dấu chấm dính liền) so với "chung" (đứng giữa câu, không dấu chấm) do câu
     * sau chèn thêm chữ, 2 token này KHÁC NHAU dù cùng 1 từ → cả từ bị gắn nhầm "xoá + thêm" thay
     * vì chỉ dấu câu dịch chuyển vị trí, hiện chữ như bị dính vào nhau ở ranh giới highlight. Tách
     * dấu câu phổ biến thành token riêng để LCS khớp đúng phần từ không đổi.
     */
    private function splitWords(string $html): array
    {
        $text = preg_replace('/<[^>]+>/', ' ', $html) ?? '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return preg_split('/([\s,.;:!?()\[\]{}"“”‘’\'\-–—…\/])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function renderWordDiffHtml(array $beforeWords, array $afterWords): string
    {
        $m = count($beforeWords);
        $n = count($afterWords);

        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                $dp[$i][$j] = $beforeWords[$i] === $afterWords[$j]
                    ? $dp[$i + 1][$j + 1] + 1
                    : max($dp[$i + 1][$j], $dp[$i][$j + 1]);
            }
        }

        $html = '';
        $i = 0;
        $j = 0;
        while ($i < $m && $j < $n) {
            if ($beforeWords[$i] === $afterWords[$j]) {
                $html .= e($beforeWords[$i]);
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $html .= '<span class="bg-error/20 text-error line-through">' . e($beforeWords[$i]) . '</span>';
                $i++;
            } else {
                $html .= '<span class="bg-success/20 text-success">' . e($afterWords[$j]) . '</span>';
                $j++;
            }
        }
        while ($i < $m) {
            $html .= '<span class="bg-error/20 text-error line-through">' . e($beforeWords[$i]) . '</span>';
            $i++;
        }
        while ($j < $n) {
            $html .= '<span class="bg-success/20 text-success">' . e($afterWords[$j]) . '</span>';
            $j++;
        }

        return $html;
    }
}
