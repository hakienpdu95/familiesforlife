<?php

namespace Modules\Post\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Post\Features\ContentAnalytics\Support\GoogleAnalyticsPageMatcher;
use Modules\Post\Models\PostArticleTranslation;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\OrderBy;
use Spatie\Analytics\Period;

/**
 * spec/ga-dashboard-statistics.md §3.1 — đồng bộ định kỳ `ga_views_30d` từ GA4 về
 * post_article_translations, để cột "Lượt xem GA" (danh sách bài viết) và "Top nội dung"
 * (trang thống kê traffic) đọc từ 1 cột denormalized duy nhất, KHÔNG gọi GA API live trong
 * request danh sách (Tabulator remote sort/pagination sẽ vượt rate limit).
 */
class SyncGoogleAnalyticsStatsCommand extends Command
{
    protected $signature = 'post:sync-ga-stats';

    protected $description = 'Đồng bộ lượt xem GA4 (30 ngày) về post_article_translations.ga_views_30d';

    public function handle(GoogleAnalyticsPageMatcher $matcher): int
    {
        $syncedAt = now();

        try {
            $rows = Analytics::get(
                period: Period::days(30),
                metrics: ['screenPageViews'],
                dimensions: ['pagePath'],
                maxResults: 1000,
                orderBy: [OrderBy::metric('screenPageViews', true)],
            );
        } catch (\Throwable $e) {
            // §4 — không throw ra ngoài, log + thoát FAILURE, KHÔNG đụng tới ga_views_30d đã có
            // (giữ nguyên số liệu cũ hơn là xoá sạch chỉ vì 1 lần gọi API lỗi).
            Log::error('[GA Sync] Gọi Google Analytics API thất bại — giữ nguyên dữ liệu ga_views_30d hiện có.', [
                'message' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $matched = 0;
        foreach ($rows as $row) {
            $slug = $matcher->extractSlug($row['pagePath']);
            if (! $slug) {
                continue; // pagePath không phải bài viết (trang chủ, danh-muc/*, module khác...)
            }

            // locale bắt buộc trong WHERE — §6.1: slug chỉ unique THEO locale (không phải toàn hệ
            // thống như comment cũ ở routes/web.php ghi nhầm), và tận dụng đúng index unique
            // composite (locale, slug) đã có sẵn, không cần index slug riêng.
            $updated = PostArticleTranslation::where('locale', config('post.default_locale'))
                ->where('slug', $slug)
                ->update(['ga_views_30d' => (int) $row['screenPageViews'], 'ga_synced_at' => $syncedAt]);

            $matched += $updated; // 0 nếu slug không khớp bài nào (đã xoá/đổi slug) — bỏ qua, không lỗi batch
        }

        // Stale reset (§3.1/§3.4): bài KHÔNG xuất hiện trong lần đồng bộ này (rớt khỏi top 1000,
        // hoặc 0 view trong 30 ngày) phải về lại null — nếu không, số liệu cũ sẽ hiển thị "mãi mãi
        // đúng" dù bài đã hết traffic. Dùng Eloquent (không DB::table) để soft-delete tự loại trừ.
        PostArticleTranslation::whereNotNull('ga_views_30d')
            ->where('ga_synced_at', '<', $syncedAt)
            ->update(['ga_views_30d' => null, 'ga_synced_at' => $syncedAt]);

        $this->info("Đồng bộ xong: {$matched}/{$rows->count()} pagePath khớp bài viết.");

        return self::SUCCESS;
    }
}
