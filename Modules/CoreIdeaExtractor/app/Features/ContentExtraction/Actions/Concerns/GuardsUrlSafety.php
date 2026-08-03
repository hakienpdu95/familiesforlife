<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\Concerns;

use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\UrlFetchException;

/**
 * SSRF/redirect/charset helpers dùng chung bởi FetchArticleHtmlAction (single URL) và
 * FetchArticlesBatchAction (pooled) — tách thành trait để 2 nơi không tự implement lại
 * logic chặn SSRF theo 2 cách khác nhau (rủi ro lệch bảo mật giữa 2 action).
 */
trait GuardsUrlSafety
{
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

    /**
     * Host toàn ký tự số/hex phân tách bằng dấu chấm (VD "0177.0.0.1" — octal của 127.0.0.1,
     * "2130706433" — decimal nguyên khối của 127.0.0.1, "0x7f000001") KHÔNG qua được
     * filter_var(FILTER_VALIDATE_IP) chuẩn ở nhánh trên, NHƯNG cURL/trình duyệt vẫn diễn giải
     * NHƯ 1 địa chỉ IP thật (browser/cURL tự convert octal/decimal/hex sang IP chuẩn trước khi
     * kết nối) — trong khi dns_get_record()/gethostbyname() bên dưới lại diễn giải nó theo cách
     * KHÁC (dns_get_record("0177.0.0.1", ...) trả nhầm "177.0.0.1" — IP public — thay vì
     * "127.0.0.1" mà octal đó thật sự biểu diễn). IP được KIỂM TRA an toàn (public, hợp lệ) do đó
     * khác hẳn IP THẬT SỰ cURL sẽ kết nối tới (loopback) — bypass toàn bộ guard bên dưới, dẫn tới
     * SSRF. Domain thật KHÔNG BAO GIỜ có dạng toàn số/hex như vậy (TLD luôn có chữ) nên chặn
     * thẳng ở đây, không cần thử phân giải DNS.
     */
    private const IP_ENCODED_HOST_PATTERN = '/^(0x[0-9a-f]+|[0-9]+)(\.(0x[0-9a-f]+|[0-9]+)){0,3}$/i';

    /** @return string[] */
    private function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        if (preg_match(self::IP_ENCODED_HOST_PATTERN, $host)) {
            return [];
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
}
