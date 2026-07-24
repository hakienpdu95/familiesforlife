<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

/**
 * Kết quả 1 nguồn trong batch (spec/CoreIdeaExtractor.md — mở rộng batch, không đổi shape
 * Layer 1 hiện có). `status='success'` xuất toàn bộ field của RawExtractionData::toApiArray()
 * cộng thêm source_url/resolved_url/domain; 'blocked'/'error' xuất shape gọn hơn (không có
 * title/headings/main_content...) kèm block_reason + notes hướng dẫn xử lý (VD dùng tab
 * "Dán mã HTML" cho nguồn bị Cloudflare chặn).
 */
class BatchSourceResultData extends Data
{
    public function __construct(
        public readonly string $source_url,
        public readonly ?string $resolved_url,
        public readonly string $domain,
        public readonly string $status,
        public readonly ?string $block_reason = null,
        public readonly ?string $notes = null,
        public readonly ?RawExtractionData $extraction = null,
    ) {}

    public static function success(string $sourceUrl, ?string $resolvedUrl, string $domain, RawExtractionData $extraction): self
    {
        return new self(
            source_url: $sourceUrl,
            resolved_url: $resolvedUrl,
            domain: $domain,
            status: 'success',
            extraction: $extraction,
        );
    }

    public static function failure(string $sourceUrl, ?string $resolvedUrl, string $domain, string $status, string $blockReason, string $notes): self
    {
        return new self(
            source_url: $sourceUrl,
            resolved_url: $resolvedUrl,
            domain: $domain,
            status: $status,
            block_reason: $blockReason,
            notes: $notes,
        );
    }

    public function toApiArray(): array
    {
        if ($this->status !== 'success') {
            return [
                'source_url'   => $this->source_url,
                'resolved_url' => $this->resolved_url,
                'domain'       => $this->domain,
                'status'       => $this->status,
                'block_reason' => $this->block_reason,
                'notes'        => $this->notes,
            ];
        }

        return array_merge(
            [
                'source_url'   => $this->source_url,
                'resolved_url' => $this->resolved_url,
                'domain'       => $this->domain,
                'status'       => 'success',
            ],
            $this->extraction->toApiArray(),
        );
    }
}
