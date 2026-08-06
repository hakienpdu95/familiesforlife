<?php

namespace Modules\N8n\Features\ConnectionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §2.5/§7 — UI quản trị CHỈ có "Tắt"
 * (inbound_enabled/outbound_enabled=false) và "Xoá" (soft delete) — KHÔNG có xoá cứng. Sau
 * soft delete: tra cứu (lookup) theo name/uuid/inbound_token coi như không tồn tại (§2.5),
 * nhưng log lịch sử vẫn giữ liên kết qua connection_id (nullOnDelete chỉ kích hoạt khi
 * forceDelete(), việc UI không cung cấp).
 */
class DeleteN8nConnectionAction
{
    use AsAction;

    public function handle(N8nConnection $connection): void
    {
        $connection->delete();
    }
}
