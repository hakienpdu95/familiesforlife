<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Phân loại 1 response HTTP thất bại (status không 2xx) thành 'blocked' (bot-protection/WAF —
 * site CHỦ ĐỘNG chặn crawler, không phải lỗi) hay 'error' (lỗi HTTP thông thường). Việc này
 * CHỈ để hiển thị đúng thông điệp/JSON shape cho người dùng biết cách xử lý (VD dùng tab "Dán mã
 * HTML") — KHÔNG dùng để né tránh hay giả mạo request theo bất kỳ cách nào.
 */
class ClassifyFetchFailureAction
{
    use AsAction;

    /** Dấu hiệu trang challenge của Cloudflare (thường thấy trong body khi bị chặn bot). */
    private const CLOUDFLARE_BODY_SIGNATURES = [
        'Just a moment',
        'cf-browser-verification',
        '__cf_chl',
        'Attention Required! | Cloudflare',
        'Enable JavaScript and cookies to continue',
    ];

    /** @param array<string, mixed> $headers */
    public function handle(int $status, string $body, array $headers): array
    {
        if ($status === 429) {
            return $this->result('blocked', 'rate_limited', 'Trang giới hạn tần suất truy cập (HTTP 429) — thử lại sau hoặc dùng tab "Dán mã HTML".');
        }

        if (in_array($status, [403, 503], true)) {
            if ($this->looksLikeCloudflareChallenge($body, $headers)) {
                return $this->result('blocked', 'cloudflare_challenge', 'Trang chặn crawl tự động (Cloudflare challenge) — dùng tab "Dán mã HTML" để trích riêng nguồn này.');
            }

            return $this->result('blocked', 'bot_protection', 'Trang chặn truy cập tự động (bot protection/WAF, HTTP '.$status.') — dùng tab "Dán mã HTML" để trích riêng nguồn này.');
        }

        return $this->result('error', 'http_error', "Trang trả về mã lỗi HTTP {$status}.");
    }

    /** @param array<string, mixed> $headers */
    private function looksLikeCloudflareChallenge(string $body, array $headers): bool
    {
        foreach (array_keys($headers) as $name) {
            if (strtolower((string) $name) === 'cf-ray') {
                return true;
            }
        }

        $sample = substr($body, 0, 8192);

        foreach (self::CLOUDFLARE_BODY_SIGNATURES as $signature) {
            if (stripos($sample, $signature) !== false) {
                return true;
            }
        }

        return false;
    }

    private function result(string $status, string $blockReason, string $message): array
    {
        return ['status' => $status, 'block_reason' => $blockReason, 'message' => $message];
    }
}
