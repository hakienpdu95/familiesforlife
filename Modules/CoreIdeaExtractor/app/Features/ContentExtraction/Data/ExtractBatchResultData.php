<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

class ExtractBatchResultData extends Data
{
    /** @param BatchSourceResultData[] $sources */
    public function __construct(
        public readonly ?string $topic,
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
            'topic'            => $this->topic,
            'requested_count'  => $this->requested_count,
            'source_coverage'  => [
                'success' => $this->success_count,
                'blocked' => $this->blocked_count,
                'error'   => $this->error_count,
            ],
            'summary_note' => $this->buildSummaryNote(),
            'sources'      => array_map(
                static fn (BatchSourceResultData $s) => $s->toApiArray(),
                $this->sources,
            ),
            'processed_at' => $this->processed_at,
        ];
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
