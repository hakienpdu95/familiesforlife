<?php

namespace Modules\N8n\Features\OutboundDelivery\Services;

use Illuminate\Support\Facades\Http;
use Modules\N8n\Features\OutboundDelivery\Data\N8nSendResult;
use Modules\N8n\Features\OutboundDelivery\Exceptions\N8nConnectionNotFoundException;
use Modules\N8n\Features\OutboundDelivery\Exceptions\N8nOutboundDisabledException;
use Modules\N8n\Models\N8nConnection;
use Throwable;

/**
 * spec/N8n_Integration_Technical_Specification.md §4 — 1 service PHP duy nhất cho chiều gọi ra.
 * Bất kỳ Action/Job/Controller nào trong hệ thống gọi thẳng — module `N8n` không biết và không
 * cần biết ai gọi nó.
 */
class N8nOutboundService
{
    /**
     * @param  string  $connection  name hoặc uuid của N8nConnection
     * @param  array  $payload  dữ liệu gửi đi, tự do theo nhu cầu bên gọi
     * @param  string|null  $eventName  nhãn tự do, chỉ để ghi log
     * @param  string|null  $caller  FQCN của class đang gọi (VD static::class), chỉ để ghi log
     *
     * @throws N8nConnectionNotFoundException không tìm thấy connection theo name/uuid (kể cả
     *                                        đã soft-delete, §2.5) — lỗi cấu hình/lập trình.
     * @throws N8nOutboundDisabledException connection tồn tại nhưng outbound_enabled=false
     *                                      hoặc outbound_webhook_url rỗng — lỗi cấu hình.
     */
    public function send(string $connection, array $payload, ?string $eventName = null, ?string $caller = null): N8nSendResult
    {
        // 1. Tra theo name HOẶC uuid, loại trừ soft-deleted (§2.5, mặc định của SoftDeletes).
        $model = N8nConnection::query()
            ->where('name', $connection)
            ->orWhere('uuid', $connection)
            ->first();

        if (! $model) {
            throw N8nConnectionNotFoundException::forNameOrUuid($connection);
        }

        // 2. outbound_enabled=false HOẶC outbound_webhook_url rỗng → lỗi cấu hình, throw.
        if (! $model->outbound_enabled || empty($model->outbound_webhook_url)) {
            throw N8nOutboundDisabledException::forConnection($model->name);
        }

        // 3. Encode JSON ĐÚNG 1 LẦN — dùng lại nguyên chuỗi $body cho CẢ việc ký LẪN việc gửi
        //    (§4.2): Http::post($url, $payload) để Guzzle tự json_encode() lại KHÔNG cam kết
        //    cùng byte với chuỗi đã tự tay encode để ký, khiến n8n xác thực HMAC fail dù dữ
        //    liệu logic giống hệt.
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signature = null;
        if (! empty($model->outbound_secret)) {
            $signature = hash_hmac('sha256', $body, $model->outbound_secret);
        }

        $startedAt = microtime(true);
        $success = false;
        $httpStatus = null;
        $errorMessage = null;

        try {
            $response = Http::withBody($body, 'application/json')
                ->withHeaders($signature !== null ? [config('n8n.signature_header') => $signature] : [])
                ->timeout((int) config('n8n.outbound_timeout'))
                ->retry((int) config('n8n.outbound_max_retries'), 500)
                ->post($model->outbound_webhook_url);

            $httpStatus = $response->status();
            $success = $response->successful();

            if (! $success) {
                $errorMessage = "n8n trả HTTP {$httpStatus}";
            }
        } catch (Throwable $e) {
            // §4.1 — lỗi vận hành tạm thời (timeout, DNS, mạng...) KHÔNG throw, gói vào kết quả.
            $errorMessage = $e->getMessage();
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        // 5. Ghi log + cập nhật last_outbound_at.
        $model->outboundLogs()->create([
            'event_name' => $eventName,
            'caller' => $caller,
            'success' => $success,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'error_message' => $errorMessage,
            // §2.4 — chiều outbound do chính code nội bộ tự soạn, không phải input không tin
            // cậy, nên luôn ghi được (khác chiều inbound §5.5).
            'payload_excerpt' => mb_substr((string) $body, 0, (int) config('n8n.log_payload_max_chars')),
            'requested_at' => now(),
        ]);

        $model->update(['last_outbound_at' => now()]);

        return new N8nSendResult(
            success: $success,
            httpStatus: $httpStatus,
            durationMs: $durationMs,
            errorMessage: $errorMessage,
        );
    }
}
