<?php

namespace Modules\N8n\Features\ConnectionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Features\ConnectionManagement\Data\N8nConnectionData;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §7.4 — CHỈ sửa các field "cấu hình" (§3.2:
 * không có field nào để tự nhập inbound_token/inbound_secret/outbound_secret ở đây).
 */
class UpdateN8nConnectionAction
{
    use AsAction;

    public function handle(N8nConnection $connection, N8nConnectionData $data, int $updatedBy): N8nConnection
    {
        $connection->update([
            'name' => $data->name,
            'purpose_note' => $data->purpose_note,
            'inbound_enabled' => $data->inbound_enabled,
            'outbound_enabled' => $data->outbound_enabled,
            'outbound_webhook_url' => $data->outbound_webhook_url,
            'allowed_ip_cidrs' => $data->allowed_ip_cidrs,
            'rate_limit_per_minute' => $data->rate_limit_per_minute,
            'updated_by' => $updatedBy,
        ]);

        return $connection;
    }
}
