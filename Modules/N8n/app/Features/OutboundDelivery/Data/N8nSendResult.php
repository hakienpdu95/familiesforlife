<?php

namespace Modules\N8n\Features\OutboundDelivery\Data;

/**
 * spec/N8n_Integration_Technical_Specification.md §4/§4.2 — DTO đơn giản trả về từ
 * `N8nOutboundService::send()` cho MỌI lỗi vận hành tạm thời (n8n down, timeout, 4xx/5xx) —
 * không throw cho nhóm lỗi này (§4.1), để bên gọi tự quyết định tiếp.
 */
final readonly class N8nSendResult
{
    public function __construct(
        public bool $success,
        public ?int $httpStatus = null,
        public ?int $durationMs = null,
        public ?string $errorMessage = null,
    ) {}
}
