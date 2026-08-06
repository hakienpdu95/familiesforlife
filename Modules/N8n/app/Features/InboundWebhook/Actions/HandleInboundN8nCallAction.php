<?php

namespace Modules\N8n\Features\InboundWebhook\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Events\N8nWebhookReceived;
use Modules\N8n\Models\N8nConnection;
use Modules\N8n\Models\N8nInboundLog;

/**
 * spec/N8n_Integration_Technical_Specification.md §5.2 — trình tự xử lý inbound webhook, ĐÚNG
 * 11 bước, không đảo thứ tự (thứ tự ảnh hưởng tới việc payload có được lưu log hay không, §5.5).
 */
class HandleInboundN8nCallAction
{
    use AsAction;

    /** §5.4 — response lỗi generic dùng chung cho MỌI mã lỗi, không phân biệt nguyên nhân. */
    private const GENERIC_ERROR_BODY = ['error' => 'invalid_request'];

    public function handle(Request $request, string $token): JsonResponse
    {
        // 1. Tra theo inbound_token, loại trừ soft-deleted (mặc định SoftDeletes, §2.5).
        $connection = N8nConnection::where('inbound_token', $token)->first();

        if (! $connection) {
            $this->logAndReject(null, $request, null, 404, null, 'inbound_token không tồn tại (hoặc connection đã bị xoá mềm).');

            return $this->reject(404);
        }

        // 2. inbound_enabled=false → 404 (KHÔNG 410 — không lộ "kết nối từng/đang tồn tại", §5.2).
        if (! $connection->inbound_enabled) {
            $this->logAndReject($connection, $request, null, 404, null, 'Kết nối tồn tại nhưng inbound_enabled=false.');

            return $this->reject(404);
        }

        // 3. Content-Type application/json + Content-Length bắt buộc (từ chối chunked) + không
        //    vượt max_inbound_body_size → KHÔNG đọc tiếp body nếu sai.
        if (! $this->passesContentChecks($request)) {
            $this->logAndReject($connection, $request, null, 400, null, 'Content-Type/Content-Length không hợp lệ, thiếu, hoặc vượt max_inbound_body_size.');

            return $this->reject(400);
        }

        // 4. Đọc RAW body — KHÔNG dùng $request->all()/->json() ở bước xác thực chữ ký (§5.3).
        $rawBody = $request->getContent();

        // 5. allowed_ip_cidrs nếu có cấu hình → không khớp → 403.
        if (! $this->ipAllowed($connection, $request->ip())) {
            $this->logAndReject($connection, $request, null, 403, null, 'IP không nằm trong allowed_ip_cidrs.');

            return $this->reject(403);
        }

        // 6. inbound_secret có cấu hình → xác minh chữ ký HMAC (§5.3) → sai → 401, KHÔNG lưu
        //    payload_excerpt (§5.5 — dữ liệu request CHƯA xác thực do kẻ tấn công tuỳ ý kiểm soát).
        $signatureValid = null;
        if (! empty($connection->inbound_secret)) {
            $signatureValid = $this->signatureValid($connection, $request, $rawBody);

            if (! $signatureValid) {
                $this->logAndReject($connection, $request, false, 401, null, 'Chữ ký HMAC không khớp.');

                return $this->reject(401);
            }
        }

        // 7. Parse JSON từ raw body → lỗi cú pháp/rỗng/không phải object ở top-level → 400,
        //    KHÔNG dispatch. Decode 1 lần không-associative trước để phân biệt {} (stdClass)
        //    với [] (mảng) — json_decode(..., true) biến CẢ 2 thành PHP array [], không phân
        //    biệt được nếu decode luôn associative ngay từ đầu.
        $decodedObject = json_decode($rawBody);
        if (json_last_error() !== JSON_ERROR_NONE || ! ($decodedObject instanceof \stdClass)) {
            $this->logAndReject($connection, $request, $signatureValid, 400, null, 'Body không phải JSON object hợp lệ.');

            return $this->reject(400);
        }

        $payload = json_decode($rawBody, true);

        // 8. event_name (top-level key, tự do — case-sensitive, §5.3) → dispatch event.
        $eventName = isset($payload['event_name']) && is_string($payload['event_name']) ? $payload['event_name'] : null;
        $receivedAt = now();

        Event::dispatch(new N8nWebhookReceived($connection, $eventName, $payload, $receivedAt));

        // 9. Số listener THỰC SỰ đăng ký cho N8nWebhookReceived::class.
        $listenerCount = count(Event::getListeners(N8nWebhookReceived::class));

        // 10. Ghi log ĐẦY ĐỦ (payload_excerpt vì đã qua xác thực, §5.5), cập nhật last_inbound_at.
        N8nInboundLog::create([
            'connection_id' => $connection->id,
            'ip_address' => $request->ip(),
            'signature_valid' => $signatureValid,
            'http_status_returned' => 202,
            'event_name' => $eventName,
            'listener_count' => $listenerCount,
            'payload_excerpt' => mb_substr($rawBody, 0, (int) config('n8n.log_payload_max_chars')),
            'error_message' => null,
            'received_at' => $receivedAt,
        ]);

        $connection->update(['last_inbound_at' => $receivedAt]);

        // 11. Trả 202 body rỗng.
        return response()->json([], 202);
    }

    private function passesContentChecks(Request $request): bool
    {
        if (! str_starts_with((string) $request->header('Content-Type'), 'application/json')) {
            return false;
        }

        // Từ chối Transfer-Encoding: chunked — không hỗ trợ (§5.2 bước 3).
        if ($request->header('Transfer-Encoding') !== null) {
            return false;
        }

        $contentLength = $request->header('Content-Length');
        if ($contentLength === null || ! ctype_digit((string) $contentLength)) {
            return false;
        }

        return (int) $contentLength <= (int) config('n8n.max_inbound_body_size');
    }

    private function ipAllowed(N8nConnection $connection, ?string $ip): bool
    {
        $cidrs = $connection->allowed_ip_cidrs;

        if (empty($cidrs) || $ip === null) {
            return true; // để trống = không giới hạn IP.
        }

        foreach ($cidrs as $cidr) {
            if ($this->ipMatchesCidr($ip, (string) $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $prefix = (int) $prefix;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bytes = intdiv($prefix, 8);
        $remainderBits = $prefix % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainderBits)) & 0xFF);

        return (substr($ipBin, $bytes, 1) & $mask) === (substr($subnetBin, $bytes, 1) & $mask);
    }

    /**
     * §5.3 — HMAC-SHA256 trên RAW body, hex thường (không tiền tố), so sánh bằng hash_equals().
     */
    private function signatureValid(N8nConnection $connection, Request $request, string $rawBody): bool
    {
        $header = config('n8n.signature_header');
        $provided = (string) $request->header($header, '');

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $connection->inbound_secret);

        return hash_equals($expected, $provided);
    }

    /** §5.4 — response lỗi generic, không phân biệt nguyên nhân qua nội dung trả về. */
    private function reject(int $status): JsonResponse
    {
        return response()->json(self::GENERIC_ERROR_BODY, $status);
    }

    /**
     * Ghi log cho MỌI nhánh thất bại (bước 1-6 của §5.2) — payload_excerpt LUÔN null (§5.5),
     * error_message ghi chi tiết thật (chỉ hiển thị ở log admin, không lộ ra response §5.4).
     */
    private function logAndReject(?N8nConnection $connection, Request $request, ?bool $signatureValid, int $httpStatus, ?string $eventName, string $errorMessage): void
    {
        N8nInboundLog::create([
            'connection_id' => $connection?->id,
            'ip_address' => $request->ip(),
            'signature_valid' => $signatureValid,
            'http_status_returned' => $httpStatus,
            'event_name' => $eventName,
            'listener_count' => 0,
            'payload_excerpt' => null,
            'error_message' => $errorMessage,
            'received_at' => now(),
        ]);
    }
}
