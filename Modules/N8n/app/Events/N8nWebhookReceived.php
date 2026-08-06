<?php

namespace Modules\N8n\Events;

use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §5.6 — điểm mở rộng DUY NHẤT của module,
 * dùng event chuẩn của Laravel. 1 module tiêu thụ muốn phản ứng với n8n = thêm 1 Listener +
 * đăng ký trong `EventServiceProvider` của chính module đó (§5.6) — `N8n` không cần biết trước
 * có bao nhiêu use case, không cần registry/config trung gian nào khác.
 */
class N8nWebhookReceived
{
    public function __construct(
        public readonly N8nConnection $connection,
        public readonly ?string $eventName,
        public readonly array $payload,
        public readonly \DateTimeInterface $receivedAt,
    ) {}
}
