<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Cache HTML thô theo URL — dùng chung bởi FetchArticleHtmlAction (single) và
 * FetchArticlesBatchAction (pooled) để 2 request khác nhau (2 batch khác nhau, hoặc gọi lại
 * cùng URL) không phải fetch mạng lại trong TTL. Cache RAW HTML (trước khi parse), KHÔNG cache
 * kết quả extract đã xong — vì main_content_selector có thể khác nhau giữa 2 lần gọi cùng URL,
 * bước parse luôn phải chạy lại, chỉ bước fetch mạng mới được tái sử dụng.
 *
 * Dùng Cache facade (cache store mặc định của app), KHÔNG phải bảng DB riêng — đúng tinh thần
 * "module KHÔNG có Eloquent Model nào" (xem routes/web.php).
 */
trait CachesFetchedHtml
{
    private function cachedHtml(string $url): ?string
    {
        if (! config('core_idea_extractor.cache.enabled', true)) {
            return null;
        }

        return Cache::get($this->fetchCacheKey($url));
    }

    private function putCachedHtml(string $url, string $html): void
    {
        if (! config('core_idea_extractor.cache.enabled', true)) {
            return;
        }

        $ttl = (int) config('core_idea_extractor.cache.fetch_ttl_seconds', 3600);

        Cache::put($this->fetchCacheKey($url), $html, $ttl);
    }

    private function fetchCacheKey(string $url): string
    {
        return 'core_idea_extractor:fetch:'.sha1($url);
    }
}
