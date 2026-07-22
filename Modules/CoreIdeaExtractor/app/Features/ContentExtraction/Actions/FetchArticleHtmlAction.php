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
                throw new UrlFetchException("Trang trả về mã lỗi HTTP {$response->status()}.");
            }

            $contentType = (string) $response->header('Content-Type');

            if ($contentType !== '' && ! str_contains($contentType, 'html')) {
                throw new UrlFetchException("Nội dung không phải HTML (Content-Type: {$contentType}).");
            }

            $body     = $response->body();
            $maxBytes = (int) config('core_idea_extractor.fetch.max_content_bytes', 5 * 1024 * 1024);

            return strlen($body) > $maxBytes ? substr($body, 0, $maxBytes) : $body;
        }

        throw new UrlFetchException("Quá nhiều lượt redirect (>{$maxRedirects}).");
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
