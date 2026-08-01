<?php

namespace Modules\Post\Features\ContentAnalytics\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Post\Features\ContentAnalytics\Queries\GetAnalyticsOverviewHandler;
use Modules\Post\Features\ContentAnalytics\Queries\GetAnalyticsOverviewQuery;
use Modules\Post\Features\ContentAnalytics\Queries\GetTopViewedArticlesHandler;
use Modules\Post\Features\ContentAnalytics\Queries\GetTopViewedArticlesQuery;

/**
 * spec/ga-dashboard-statistics.md — trang "Thống kê traffic" cho đội biên tập Post (platform
 * roles, xem §1), tách biệt hoàn toàn dashboard CRM/Sales chung (App\Http\Controllers\Backend\
 * DashboardController — theo tổ chức, không liên quan property GA4 của toàn site).
 *
 * Chọn kỳ (7/30/tuỳ chỉnh) qua query string `?days=` (GET reload thường, KHÔNG AJAX) — trang
 * xem thủ công tần suất thấp, cùng nguyên tắc clicks.blade.php (không cần remote sort/pagination
 * như Tabulator danh sách bài viết).
 */
class ContentAnalyticsDashboardController extends Controller
{
    public function index(
        Request $request,
        GetAnalyticsOverviewHandler $overviewHandler,
        GetTopViewedArticlesHandler $topViewedHandler,
    ): View {
        abort_unless($request->user()->can('post_analytics.view'), 403);

        $days = $request->integer('days', 30);
        $days = min(max($days, 7), 90);

        // §4 — điểm xử lý graceful degradation DUY NHẤT: mọi lỗi gọi Google Analytics API (thiếu
        // credentials, property ID sai, 403/429...) rơi vào đây, KHÔNG để lộ exception 500.
        $overview = null;
        $error    = null;

        try {
            $overview = $overviewHandler->handle(new GetAnalyticsOverviewQuery(days: $days));
        } catch (\Throwable $e) {
            Log::warning('[GA Dashboard] Không tải được dữ liệu Google Analytics.', [
                'message' => $e->getMessage(),
            ]);

            $error = 'Không thể tải dữ liệu Google Analytics. Kiểm tra cấu hình ANALYTICS_PROPERTY_ID / Service Account trong .env.';
        }

        // Top nội dung đọc từ ga_views_30d (cột DB, không gọi GA) — không phụ thuộc vào việc
        // overview có lỗi hay không, luôn hiển thị được nếu đã từng chạy sync ít nhất 1 lần.
        $topArticles = $topViewedHandler->handle(new GetTopViewedArticlesQuery(limit: 10));

        return view('post::admin.articles.analytics', [
            'days'        => $days,
            'overview'    => $overview,
            'error'       => $error,
            'topArticles' => $topArticles,
        ]);
    }
}
