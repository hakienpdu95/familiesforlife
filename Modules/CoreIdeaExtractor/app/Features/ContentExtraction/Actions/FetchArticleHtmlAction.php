<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\UrlFetchException;

/**
 * Fetch HTML từ 1 URL bất kỳ do người dùng nhập — server-side, nên PHẢI chặn SSRF (dò IP nội
 * bộ/private/loopback/metadata endpoint cloud). Tắt auto-follow-redirect của Guzzle, tự theo
 * dõi tối đa `max_redirects` lần, validate lại IP ở MỖI hop (chặn kiểu "URL công khai redirect
 * sang IP nội bộ").
 *
 * Giới hạn còn lại (chấp nhận được cho tool nội bộ, không phải endpoint công khai đại chúng):
 * đây là kiểm tra TOCTOU — IP được validate ở bước dns_get_record() có thể khác IP thật cURL
 * kết nối tới (DNS rebinding). Muốn triệt để cần ép cURL CURLOPT_RESOLVE vào đúng IP đã kiểm
 * tra — không làm ở bản này vì đây là công cụ dùng nội bộ bởi user đã đăng nhập, không phải
 * endpoint public.
 */
class FetchArticleHtmlAction
{
    use AsAction;

    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    public function handle(string $url): string
    {
        $current      = $url;
        $maxRedirects = (int) config('core_idea_extractor.fetch.max_redirects', 3);

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            $this->assertSafeUrl($current);

            $response = Http::withHeaders(['User-Agent' => config('core_idea_extractor.fetch.user_agent')])
                ->timeout((int) config('core_idea_extractor.fetch.timeout_seconds', 15))
                ->withOptions(['allow_redirects' => false])
                ->get($current);

            if (in_array($response->status(), self::REDIRECT_STATUSES, true)) {
                $location = $response->header('Location');

                if (! $location) {
                    throw new UrlFetchException('Redirect không có header Location.');
                }

                $current = $this->resolveRedirectUrl($current, $location);

                continue;
            }

            if (! $response->successful()) {
                // 403/429 thường là do WAF/bot-management (Cloudflare, BigScoots...) chặn thẳng
                // request không phải trình duyệt thật — module dùng User-Agent minh bạch (xem
                // config('core_idea_extractor.fetch.user_agent')), không giả mạo browser để né
                // chặn, nên với site chặn kiểu này việc fetch thất bại là theo đúng thiết kế của
                // site đó, không phải lỗi code. Chú thích rõ cho người dùng thay vì để mã lỗi trơ.
                $hint = in_array($response->status(), [403, 429], true)
                    ? ' Trang có thể đang chặn truy cập tự động (bot protection/WAF) — không thể trích xuất bằng công cụ này.'
                    : '';

                throw new UrlFetchException("Trang trả về mã lỗi HTTP {$response->status()}.{$hint}");
            }

            $contentType = (string) $response->header('Content-Type');

            if ($contentType !== '' && ! str_contains($contentType, 'html')) {
                throw new UrlFetchException("Nội dung không phải HTML (Content-Type: {$contentType}).");
            }

            $body     = $this->normalizeToUtf8($response->body(), $contentType);
            $maxBytes = (int) config('core_idea_extractor.fetch.max_content_bytes', 5 * 1024 * 1024);

            return strlen($body) > $maxBytes ? substr($body, 0, $maxBytes) : $body;
        }

        throw new UrlFetchException("Quá nhiều lượt redirect (>{$maxRedirects}).");
    }

    /**
     * ExtractRawContentAction giả định $html trả ra từ đây LUÔN LÀ UTF-8 (cần cho fix parse
     * charset ở đó) — nên site khai báo charset khác UTF-8 (VD windows-1258, thường gặp ở site
     * Việt Nam cũ) phải được convert ở đây, TRƯỚC khi tới tay parser.
     */
    private function normalizeToUtf8(string $body, string $contentTypeHeader): string
    {
        $charset = $this->detectCharset($body, $contentTypeHeader);

        if ($charset === null || in_array(strtolower($charset), ['utf-8', 'utf8'], true)) {
            return $body;
        }

        $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

        return $converted !== false ? $converted : $body;
    }

    private function detectCharset(string $body, string $contentTypeHeader): ?string
    {
        if (preg_match('/charset=["\']?([a-zA-Z0-9._-]+)/i', $contentTypeHeader, $m)) {
            return trim($m[1], "\"' ");
        }

        // Charset khai qua <meta> luôn nằm trong <head>, gần đầu document — chỉ cần quét vài KB
        // đầu, không cần quét cả body (tốn CPU vô ích với response vài MB).
        if (preg_match('/<meta[^>]+charset=["\']?([a-zA-Z0-9._-]+)/i', substr($body, 0, 4096), $m)) {
            return trim($m[1], "\"' ");
        }

        return null;
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            throw new UrlFetchException('URL không hợp lệ.');
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new UrlFetchException('Chỉ hỗ trợ URL http/https.');
        }

        $host = $parts['host'];
        $ips  = $this->resolveIps($host);

        if (empty($ips)) {
            throw new UrlFetchException("Không phân giải được domain \"{$host}\".");
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new UrlFetchException('URL trỏ tới địa chỉ IP nội bộ/riêng tư — không được phép fetch.');
            }
        }
    }

    /** @return string[] */
    private function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips     = [];
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (! empty($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if (empty($ips)) {
            $ip = @gethostbyname($host);
            if ($ip !== $host) {
                $ips[] = $ip;
            }
        }

        return $ips;
    }

    private function resolveRedirectUrl(string $current, string $location): string
    {
        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return $location;
        }

        $base   = parse_url($current);
        $scheme = $base['scheme'] ?? 'https';
        $host   = $base['host'] ?? '';
        $port   = isset($base['port']) ? ':'.$base['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $path = rtrim(dirname($base['path'] ?? '/'), '/');

        return "{$scheme}://{$host}{$port}{$path}/{$location}";
    }
}
