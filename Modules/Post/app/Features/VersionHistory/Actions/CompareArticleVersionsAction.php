<?php

namespace Modules\Post\Features\VersionHistory\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticleVersion;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §12 — diff v1 cố tình đơn giản: field
 * scalar (trước/sau) + block diff THEO VỊ TRÍ (không LCS, không word-level, §12.2/§12.3). Giới
 * hạn đã biết: chèn 1 block ở giữa làm lệch chỉ số các block phía sau (nhãn "Thay đổi" sai thay
 * vì "Thêm mới") — chấp nhận được ở v1, xem §12.2.
 */
class CompareArticleVersionsAction
{
    use AsAction;

    private const SCALAR_FIELDS = [
        'title', 'slug', 'excerpt', 'seo_title', 'seo_description',
        'disclosure_text', 'cta_text', 'cta_url',
    ];

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
        $count = max(count($before), count($after));
        $changes = [];

        for ($i = 0; $i < $count; $i++) {
            $beforeBlock = $before[$i] ?? null;
            $afterBlock  = $after[$i] ?? null;

            if ($beforeBlock !== null && $afterBlock === null) {
                $changes[] = ['index' => $i, 'status' => 'removed', 'type' => $beforeBlock['type'], ...$this->blockPayload($beforeBlock, 'before')];
                continue;
            }

            if ($beforeBlock === null && $afterBlock !== null) {
                $changes[] = ['index' => $i, 'status' => 'added', 'type' => $afterBlock['type'], ...$this->blockPayload($afterBlock, 'after')];
                continue;
            }

            if ($this->blocksEqual($beforeBlock, $afterBlock)) {
                $changes[] = ['index' => $i, 'status' => 'unchanged', 'type' => $afterBlock['type']];
                continue;
            }

            $changes[] = [
                'index'  => $i,
                'status' => 'changed',
                'type'   => $afterBlock['type'],
                ...$this->blockPayload($beforeBlock, 'before'),
                ...$this->blockPayload($afterBlock, 'after'),
                ...$this->productSummary($beforeBlock, $afterBlock),
            ];
        }

        return $changes;
    }

    /** §12.2 mục 2 — text: so `text_html` sau strip_tags+trim; product: so toàn bộ cấu hình. */
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

    /**
     * §12.2 mục 3 — với block product bị "Thay đổi", tóm tắt template + array_diff danh sách
     * product_id (không diff chi tiết từng override field).
     */
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
}
