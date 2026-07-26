<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

class ExtractBatchResultData extends Data
{
    /** @param BatchSourceResultData[] $sources */
    public function __construct(
        public readonly ?string $topic,
        public readonly ?string $audience,
        public readonly ?string $goal,
        public readonly ?string $constraints,
        public readonly ?string $style_sample,
        public readonly string $processed_at,
        public readonly int $requested_count,
        public readonly int $success_count,
        public readonly int $blocked_count,
        public readonly int $error_count,
        public readonly array $sources,
    ) {}

    public function toApiArray(): array
    {
        return [
            'topic' => $this->topic,
            /**
             * Ngữ cảnh phía người viết (audience/goal/constraints) — khác `sources[]` (ngữ cảnh
             * phía nguồn). Thiếu phần này, dán nguyên JSON vào chat AI vẫn dễ ra câu trả lời
             * chung chung dù sources có sâu đến đâu (AI không biết viết cho ai, để làm gì, giới
             * hạn gì) — xem thảo luận "why AI gives generic answers" đã tham khảo khi thiết kế.
             */
            'brief' => [
                'audience'     => $this->audience,
                'goal'         => $this->goal,
                'constraints'  => $this->constraints,
                'style_sample' => $this->style_sample,
            ],
            'requested_count'  => $this->requested_count,
            'source_coverage'  => [
                'success' => $this->success_count,
                'blocked' => $this->blocked_count,
                'error'   => $this->error_count,
            ],
            /**
             * Giao (case-insensitive) của keywords các nguồn THÀNH CÔNG — tính bằng PHP thuần
             * (array_intersect), KHÔNG suy luận ngữ nghĩa. Chỉ có ý nghĩa khi so sánh được từ 2
             * nguồn trở lên — cho AI 1 điểm khởi đầu cụ thể khi tổng hợp ý tưởng chéo nguồn, thay
             * vì phải tự dò qua từng danh sách keywords của 7 nguồn bằng mắt.
             */
            'common_keywords' => $this->buildCommonKeywords(),
            'summary_note' => $this->buildSummaryNote(),
            'sources'      => array_map(
                static fn (BatchSourceResultData $s) => $s->toApiArray(),
                $this->sources,
            ),
            'processed_at' => $this->processed_at,
        ];
    }

    /** @return string[] */
    private function buildCommonKeywords(): array
    {
        $lists = array_values(array_filter(array_map(
            static fn (BatchSourceResultData $s) => ($s->status === 'success' && $s->keywords !== []) ? $s->keywords : null,
            $this->sources,
        )));

        if (count($lists) < 2) {
            return [];
        }

        $normalized = array_map(
            static fn (array $kws) => array_unique(array_map(static fn (string $k) => mb_strtolower(trim($k)), $kws)),
            $lists,
        );

        return array_values(array_intersect(...$normalized));
    }

    /**
     * Ghi chú tổng hợp cấp batch — khi phần lớn/toàn bộ nguồn không trích được tự động, phóng
     * viên cần biết ngay mà không phải đọc hết `notes`/`error_message` của từng nguồn mới hiểu
     * tình hình (VD cả 7 nguồn đều 'blocked' — per-source error_message có nhưng dễ bị bỏ sót
     * khi lướt nhanh).
     */
    private function buildSummaryNote(): ?string
    {
        if ($this->success_count === $this->requested_count) {
            return null;
        }

        $problem = $this->blocked_count + $this->error_count;

        if ($this->success_count === 0) {
            return "Không trích được nguồn nào tự động ({$this->blocked_count} bị chặn, {$this->error_count} lỗi) — xem failure_type từng nguồn, dùng tab \"Dán mã HTML\" để trích thủ công.";
        }

        return "{$problem}/{$this->requested_count} nguồn không trích được tự động ({$this->blocked_count} bị chặn, {$this->error_count} lỗi) — xem failure_type từng nguồn, dùng tab \"Dán mã HTML\" nếu cần bổ sung.";
    }
}
