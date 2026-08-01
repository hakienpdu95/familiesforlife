<?php

namespace Modules\Post\Features\ContentAnalytics\Support;

/**
 * spec/ga-dashboard-statistics.md §6.1 — GA4 trả `pagePath` cho MỌI trang công khai của site
 * (trang chủ, danh-muc/*, tac-gia/*, module khác như /anland, /su-kien/*...), không chỉ bài
 * viết. Chỉ path khớp đúng mẫu route `{slug}-d{id}.html` (xem Modules/Post/routes/web.php,
 * ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])) mới là bài viết — phần còn lại bỏ qua,
 * không thử map. `id` chỉ dùng để NHẬN DIỆN đây là path bài viết, không dùng để tra cứu (tra
 * theo `slug`, xem PublicArticleController::show()).
 */
class GoogleAnalyticsPageMatcher
{
    private const PATTERN = '/^\/(?<slug>[a-z0-9\-]+)-d(?<id>[0-9]+)\.html$/';

    public function extractSlug(string $pagePath): ?string
    {
        if (! preg_match(self::PATTERN, $pagePath, $matches)) {
            return null;
        }

        return $matches['slug'];
    }
}
