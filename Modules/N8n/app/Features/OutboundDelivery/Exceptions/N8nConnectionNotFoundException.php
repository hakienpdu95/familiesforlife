<?php

namespace Modules\N8n\Features\OutboundDelivery\Exceptions;

use RuntimeException;

/**
 * spec/N8n_Integration_Technical_Specification.md §4.1 — không tìm thấy connection theo
 * name/uuid (kể cả đã soft-delete, §2.5). LỖI CẤU HÌNH/LẬP TRÌNH (tên gõ sai) — throw để fail
 * nhanh và lộ ra ngay ở test/log lỗi, KHÔNG nuốt thành success=false.
 */
class N8nConnectionNotFoundException extends RuntimeException
{
    public static function forNameOrUuid(string $connection): self
    {
        return new self("N8nConnection không tồn tại (hoặc đã bị xoá mềm): \"{$connection}\".");
    }
}
