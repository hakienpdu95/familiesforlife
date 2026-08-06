<?php

namespace Modules\N8n\Features\ConnectionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Features\ConnectionManagement\Data\N8nConnectionData;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §3.2 — sinh inbound_token/inbound_secret,
 * KHÔNG cho nhập tay. `inbound_token`/`inbound_secret` được trả về PLAINTEXT 1 LẦN DUY NHẤT
 * qua giá trị trả về của Action này — Controller đọc thẳng `$connection->inbound_token` (chưa
 * bị mask) và `$connection->inbound_secret` (accessor giải mã 'encrypted' cast) ngay sau khi
 * tạo, trước khi model bị serialize lại ở lần load kế tiếp.
 */
class CreateN8nConnectionAction
{
    use AsAction;

    public function handle(N8nConnectionData $data, int $createdBy): N8nConnection
    {
        return N8nConnection::create([
            'name' => $data->name,
            'purpose_note' => $data->purpose_note,
            'inbound_enabled' => $data->inbound_enabled,
            'outbound_enabled' => $data->outbound_enabled,
            // 256-bit entropy, hex — cùng thuật toán cho cả token định tuyến lẫn secret xác
            // thực (§3.2). KHÔNG dùng Str::random() (base62) — hex thống nhất, dễ so sánh/copy.
            'inbound_token' => bin2hex(random_bytes(32)),
            'inbound_secret' => bin2hex(random_bytes(32)),
            'outbound_webhook_url' => $data->outbound_webhook_url,
            // outbound_secret KHÔNG tự sinh ở đây — NULL = gửi không ký, 1 lựa chọn hợp lệ
            // (§4.1), khác inbound_secret luôn có ngay từ đầu. Admin chủ động "Xoay outbound
            // secret" (RotateN8nConnectionSecretAction) khi muốn n8n xác thực chiều outbound.
            'outbound_secret' => null,
            'allowed_ip_cidrs' => $data->allowed_ip_cidrs,
            'rate_limit_per_minute' => $data->rate_limit_per_minute,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
