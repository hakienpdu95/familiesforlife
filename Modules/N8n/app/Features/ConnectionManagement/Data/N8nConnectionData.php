<?php

namespace Modules\N8n\Features\ConnectionManagement\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở Store/UpdateN8nConnectionRequest::rules() (cùng pattern BannerData) —
 * DTO này chỉ hydrate dữ liệu đã qua validate, dùng chung cho cả tạo mới và sửa.
 */
class N8nConnectionData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $purpose_note = null,
        public readonly bool $inbound_enabled = false,
        public readonly bool $outbound_enabled = false,
        public readonly ?string $outbound_webhook_url = null,
        public readonly ?array $allowed_ip_cidrs = null,
        public readonly ?int $rate_limit_per_minute = null,
    ) {}
}
