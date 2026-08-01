<?php

namespace Modules\Post\Features\ContentAnalytics\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/ga-dashboard-statistics.md §2.1 — đọc THẲNG từ ga_views_30d đã đồng bộ sẵn
 * (SyncGoogleAnalyticsStatsCommand), KHÔNG gọi Analytics::fetchMostVisitedPages() — hàm đó dùng
 * dimension fullPageUrl/pageTitle (khác pagePath dùng để đồng bộ), trộn 2 nguồn sẽ ra 2 con số
 * lệch nhau cho cùng khái niệm "bài xem nhiều" so với cột "Lượt xem GA" ở danh sách bài viết.
 */
class GetTopViewedArticlesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetTopViewedArticlesQuery $query */
        return PostArticleTranslation::whereNotNull('ga_views_30d')
            ->with('article')
            ->orderByDesc('ga_views_30d')
            ->limit($query->limit)
            ->get();
    }
}
