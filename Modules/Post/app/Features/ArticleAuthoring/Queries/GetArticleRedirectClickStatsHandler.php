<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Post\Models\PostArticleRedirectClick;

class GetArticleRedirectClickStatsHandler implements QueryHandlerInterface
{
    /**
     * @return array{
     *     total: int,
     *     byDay: Collection<int, array{day: string, count: int}>,
     *     topReferrers: Collection<int, array{referrer: string, count: int}>,
     * }
     */
    public function handle(QueryInterface $query): array
    {
        /** @var GetArticleRedirectClickStatsQuery $query */
        $from = now()->subDays($query->days - 1)->startOfDay();

        $total = PostArticleRedirectClick::where('article_id', $query->articleId)->count();

        $countsByDay = PostArticleRedirectClick::where('article_id', $query->articleId)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $byDay = collect();
        for ($i = $query->days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $byDay->push(['day' => $day, 'count' => (int) ($countsByDay[$day] ?? 0)]);
        }

        // referrer bắt tại route /bai-viet/{slug} — với click NỘI BỘ (người dùng đang lướt
        // chính site này rồi bấm vào card), Referer LUÔN LÀ domain của chính site → gộp theo
        // host sẽ ra 1 dòng lặp lại vô nghĩa (chỉ nói "click đến từ site của bạn", việc hiển
        // nhiên). Phân biệt 2 trường hợp:
        //   - Referer CÙNG domain với site này → lấy PATH (vd "/", "/bai-viet/danh-muc/...")
        //     để biết ĐANG Ở TRANG NÀO trên site khi bấm — đây là thông tin thật sự hữu ích.
        //   - Referer KHÁC domain (Facebook, Google...) → giữ lại domain, đúng là nguồn ngoài.
        $ownHost = request()->getHost();

        $topReferrers = PostArticleRedirectClick::where('article_id', $query->articleId)
            ->where('created_at', '>=', $from)
            ->whereNotNull('referrer')
            ->pluck('referrer')
            ->map(function (string $url) use ($ownHost) {
                $host = parse_url($url, PHP_URL_HOST);

                if ($host === null) {
                    return $url;
                }

                if ($host === $ownHost) {
                    $path = parse_url($url, PHP_URL_PATH) ?: '/';
                    $query = parse_url($url, PHP_URL_QUERY);

                    return $path . ($query ? "?{$query}" : '');
                }

                return $host;
            })
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $source) => ['referrer' => $source, 'count' => $count])
            ->values();

        return [
            'total'        => $total,
            'byDay'        => $byDay,
            'topReferrers' => $topReferrers,
        ];
    }
}
