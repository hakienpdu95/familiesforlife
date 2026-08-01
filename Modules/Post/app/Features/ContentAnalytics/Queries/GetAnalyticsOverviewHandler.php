<?php

namespace Modules\Post\Features\ContentAnalytics\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Carbon;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;

/**
 * spec/ga-dashboard-statistics.md §2.1 — KHÔNG bọc try/catch ở đây; lỗi gọi Google Analytics
 * API phải thoát ra tới ContentAnalyticsDashboardController (điểm xử lý graceful degradation
 * duy nhất, §4), Handler chỉ lo build đúng số liệu khi API trả về bình thường.
 */
class GetAnalyticsOverviewHandler implements QueryHandlerInterface
{
    /**
     * @return array{
     *     summary: array{activeUsers: int, pageViews: int, sessions: int, activeUsersChangePct: ?float, pageViewsChangePct: ?float, sessionsChangePct: ?float},
     *     timeseries: array<int, array{date: string, activeUsers: int, screenPageViews: int}>,
     *     referrers: \Illuminate\Support\Collection,
     *     countries: \Illuminate\Support\Collection,
     *     browsers: \Illuminate\Support\Collection,
     *     devices: \Illuminate\Support\Collection,
     *     userTypes: \Illuminate\Support\Collection,
     * }
     */
    public function handle(QueryInterface $query): array
    {
        /** @var GetAnalyticsOverviewQuery $query */
        $days = $query->days;

        $currentPeriod = Period::days($days);

        // So sánh kỳ trước (§2.1) — lùi thêm 1 ngày so với điểm bắt đầu kỳ hiện tại để không đếm
        // trùng ranh giới (Period::days() coi cả 2 mốc là NGÀY, inclusive 2 đầu).
        $previousEnd    = Carbon::today()->subDays($days + 1);
        $previousStart  = $previousEnd->copy()->subDays($days);
        $previousPeriod = Period::create($previousStart, $previousEnd);

        $currentRows  = Analytics::get(
            period: $currentPeriod,
            metrics: ['activeUsers', 'screenPageViews', 'sessions'],
            dimensions: ['date'],
        );
        $previousRows = Analytics::get(
            period: $previousPeriod,
            metrics: ['activeUsers', 'screenPageViews', 'sessions'],
            dimensions: ['date'],
        );

        $activeUsers = (int) $currentRows->sum('activeUsers');
        $pageViews   = (int) $currentRows->sum('screenPageViews');
        $sessions    = (int) $currentRows->sum(fn ($row) => (int) $row['sessions']);

        $previousActiveUsers = (int) $previousRows->sum('activeUsers');
        $previousPageViews   = (int) $previousRows->sum('screenPageViews');
        $previousSessions    = (int) $previousRows->sum(fn ($row) => (int) $row['sessions']);

        return [
            'summary' => [
                'activeUsers'          => $activeUsers,
                'pageViews'            => $pageViews,
                'sessions'             => $sessions,
                'activeUsersChangePct' => $this->percentChange($activeUsers, $previousActiveUsers),
                'pageViewsChangePct'   => $this->percentChange($pageViews, $previousPageViews),
                'sessionsChangePct'    => $this->percentChange($sessions, $previousSessions),
            ],
            'timeseries' => $currentRows
                ->sortBy('date')
                ->map(fn ($row) => [
                    'date'            => $row['date']->format('Y-m-d'),
                    'activeUsers'     => $row['activeUsers'],
                    'screenPageViews' => $row['screenPageViews'],
                ])
                ->values()
                ->all(),
            'referrers' => Analytics::fetchTopReferrers($currentPeriod),
            'countries' => Analytics::fetchTopCountries($currentPeriod),
            'browsers'  => Analytics::fetchTopBrowsers($currentPeriod),
            'devices'   => Analytics::get(
                period: $currentPeriod,
                metrics: ['screenPageViews'],
                dimensions: ['deviceCategory'],
            ),
            'userTypes' => Analytics::fetchUserTypes($currentPeriod),
        ];
    }

    /** Không so sánh được (kỳ trước = 0) → null, FE hiển thị "—" thay vì chia cho 0 / "∞%". */
    private function percentChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
