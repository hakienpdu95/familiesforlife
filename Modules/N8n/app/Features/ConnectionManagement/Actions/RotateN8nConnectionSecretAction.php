<?php

namespace Modules\N8n\Features\ConnectionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §3.2 — xoay CHỌN LỌC, 3 tham số độc lập
 * (không bắt buộc xoay cả 3 cùng lúc). Trả về mảng CHỈ chứa giá trị plaintext của (các) field
 * VỪA xoay — VD chỉ xoay outbound_secret thì không trả lại inbound_token cũ, tránh hiểu lầm là
 * giá trị cũng vừa đổi.
 *
 * Xoay inbound_token = ĐỔI URL webhook (khác bản chất xoay secret, URL giữ nguyên) — n8n phải
 * cập nhật lại URL nhận webhook ngay, URL cũ ngừng nhận request ngay lập tức.
 */
class RotateN8nConnectionSecretAction
{
    use AsAction;

    public function handle(
        N8nConnection $connection,
        bool $rotateInboundToken = false,
        bool $rotateInboundSecret = false,
        bool $rotateOutboundSecret = false,
        ?int $updatedBy = null,
    ): array {
        $rotated = [];
        $updates = [];

        if ($rotateInboundToken) {
            $updates['inbound_token'] = $rotated['inbound_token'] = bin2hex(random_bytes(32));
        }

        if ($rotateInboundSecret) {
            $updates['inbound_secret'] = $rotated['inbound_secret'] = bin2hex(random_bytes(32));
        }

        if ($rotateOutboundSecret) {
            $updates['outbound_secret'] = $rotated['outbound_secret'] = bin2hex(random_bytes(32));
        }

        if ($updates !== []) {
            $updates['updated_by'] = $updatedBy;
            $connection->update($updates);
        }

        return $rotated;
    }
}
