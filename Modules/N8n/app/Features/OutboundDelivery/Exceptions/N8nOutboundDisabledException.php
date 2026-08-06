<?php

namespace Modules\N8n\Features\OutboundDelivery\Exceptions;

use RuntimeException;

/**
 * spec/N8n_Integration_Technical_Specification.md §4.1 — connection tồn tại nhưng
 * outbound_enabled=false hoặc outbound_webhook_url rỗng. Cũng là lỗi CẤU HÌNH (ai đó tắt
 * kết nối mà code vẫn gọi), throw để phát hiện sớm.
 */
class N8nOutboundDisabledException extends RuntimeException
{
    public static function forConnection(string $name): self
    {
        return new self("N8nConnection \"{$name}\" đang tắt outbound hoặc chưa cấu hình outbound_webhook_url.");
    }
}
