<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\Concerns\CachesFetchedHtml;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\Concerns\GuardsUrlSafety;
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
    use AsAction, GuardsUrlSafety, CachesFetchedHtml;

    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    public function handle(string $url, bool $forceRefresh = false): string
    {
        if (! $forceRefresh) {
            $cached = $this->cachedHtml($url);

            if ($cached !== null) {
                return $cached;
            }
        }

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
            $body     = strlen($body) > $maxBytes ? substr($body, 0, $maxBytes) : $body;

            $this->putCachedHtml($url, $body);

            return $body;
        }

        throw new UrlFetchException("Quá nhiều lượt redirect (>{$maxRedirects}).");
    }

}
