<?php

namespace Modules\N8n\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\N8n\Features\OutboundDelivery\Services\N8nOutboundService;

/**
 * spec/N8n_Integration_Technical_Specification.md §4 — facade tiện dụng (tuỳ chọn, không bắt
 * buộc dùng), trỏ vào `N8nOutboundService`. Không thêm logic.
 *
 * @method static \Modules\N8n\Features\OutboundDelivery\Data\N8nSendResult send(string $connection, array $payload, ?string $eventName = null, ?string $caller = null)
 *
 * @see N8nOutboundService
 */
class N8n extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return N8nOutboundService::class;
    }
}
