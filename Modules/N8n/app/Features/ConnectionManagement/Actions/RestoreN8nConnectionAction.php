<?php

namespace Modules\N8n\Features\ConnectionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §7.1 — muốn dùng lại đúng tên của 1 kết nối
 * đã xoá mềm, PHẢI restore() bản ghi cũ thay vì tạo mới (name không bao giờ được giải phóng,
 * nên restore() không có rủi ro trùng tên với kết nối nào khác đang tồn tại).
 */
class RestoreN8nConnectionAction
{
    use AsAction;

    public function handle(N8nConnection $connection): N8nConnection
    {
        $connection->restore();

        return $connection;
    }
}
