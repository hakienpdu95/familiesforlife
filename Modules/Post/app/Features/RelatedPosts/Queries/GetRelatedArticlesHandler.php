<?php

namespace Modules\Post\Features\RelatedPosts\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/Related_Posts_Engine_Technical_Specification.md §5 — thuật toán tính điểm liên quan
 * (category + tag + hành vi đồng-xem + độ phổ biến), tính realtime lúc request + cache theo
 * article_id (§0 "Thời điểm tính gợi ý", TTL config('post.related_posts.cache_ttl_hours')).
 */
class GetRelatedArticlesHandler implements QueryHandlerInterface
{
    /** @return Collection<int, PostArticleTranslation> */
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetRelatedArticlesQuery $query */
        $ttlHours = (int) config('post.related_posts.cache_ttl_hours', 6);
        $cacheKey = "related_posts:{$query->articleId}:{$query->locale}:{$query->limit}";

        // Cache CHỈ mảng translation_id (int[] phẳng), KHÔNG cache thẳng Collection Eloquent
        // (model + relation lồng nhau article->categories/tags...) — cache store 'database' dùng
        // PHP serialize()/unserialize() thô, RẤT dễ vỡ nếu shape class đổi giữa lúc ghi cache và
        // lúc đọc (deploy mới, sửa model, đổi migration...): unserialize() khi đó có thể trả về
        // __PHP_Incomplete_Class thay vì object thật, gây TypeError ngay tại khai báo return type
        // của hàm này (đã gặp thật trong lúc phát triển). Mảng số nguyên thuần luôn
        // serialize/unserialize đúng bất kể model đổi thế nào — đánh đổi 1 câu query nhẹ theo
        // khoá chính ở hydrate() (kể cả khi cache hit) để lấy sự bền vững đó, đồng thời tự "lành"
        // nếu 1 id trong cache đã bị unpublish/xoá giữa chừng (§5.5 tinh thần luôn trả kết quả
        // đúng, không phải dữ liệu cache có thể đã lỗi thời).
        $translationIds = Cache::remember(
            $cacheKey,
            now()->addHours($ttlHours),
            fn () => $this->compute($query)->pluck('id')->all(),
        );

        return $this->hydrate($translationIds, $query->locale);
    }

    /**
     * Truy vấn lại model thật theo đúng thứ tự id đã cache — whereIn() không đảm bảo thứ tự nên
     * phải tự sắp lại theo $translationIds; lọc bỏ id không còn published (đã unpublish/xoá).
     *
     * @param int[] $translationIds
     * @return Collection<int, PostArticleTranslation>
     */
    private function hydrate(array $translationIds, string $locale): Collection
    {
        if ($translationIds === []) {
            return collect();
        }

        $translations = PostArticleTranslation::published()
            ->where('locale', $locale)
            ->whereIn('id', $translationIds)
            ->with(['article.categories', 'article.tags'])
            ->get()
            ->keyBy('id');

        return collect($translationIds)
            ->map(fn (int $id) => $translations->get($id))
            ->filter()
            ->values();
    }

    /** @return Collection<int, PostArticleTranslation> */
    private function compute(GetRelatedArticlesQuery $query): Collection
    {
        $article = PostArticle::with(['categories', 'tags'])->find($query->articleId);

        if (! $article) {
            return collect();
        }

        $sourceCategoryIds = $article->categories->pluck('id')->all();
        $sourceTagIds      = $article->tags->pluck('id')->all();
        $weights           = (array) config('post.related_posts.weights', []);

        $coOccurrenceCounts = $this->coOccurringArticleIds($article->id);

        // §5.1 — pool CHỈ hợp lệ khi có ít nhất 1 trong 3 điều kiện lọc. Nếu cả 3 đều rỗng (bài
        // nguồn không category, không tag, chưa có lượt đồng-xem nào), whereHas(...)->where(fn
        // ($sub) => ...) bên dưới sẽ nhận 1 closure KHÔNG thêm where nào — Laravel bỏ qua nhóm
        // where rỗng (Builder::addNestedWhereQuery() no-op khi $query->wheres rỗng), khiến pool
        // vô tình khớp TOÀN BỘ bài published thay vì đúng ý "phải thoả 1 trong 3 điều kiện". Chặn
        // tay ở đây, rơi thẳng về popularFallback() (điểm = 0, đúng bản chất "lấp chỗ trống").
        $hasAnyFilterSignal = $sourceCategoryIds !== [] || $sourceTagIds !== [] || $coOccurrenceCounts->isNotEmpty();

        $pool = $hasAnyFilterSignal
            ? $this->candidatePool($query, $article->id, $sourceCategoryIds, $sourceTagIds, $coOccurrenceCounts->keys()->all())
            : collect();

        $scored = $pool
            ->map(fn (PostArticleTranslation $candidate) => [
                'translation' => $candidate,
                'score'       => $this->score($candidate, $sourceCategoryIds, $sourceTagIds, $coOccurrenceCounts, $weights),
            ])
            ->filter(fn (array $row) => $row['score'] > 0)
            // §5.4 — điểm cao hơn thắng; hoà điểm thì published_at mới hơn thắng (tie-break ổn
            // định giữa các lần cache miss).
            ->sort(fn (array $a, array $b) => $b['score'] <=> $a['score']
                ?: $b['translation']->published_at <=> $a['translation']->published_at)
            ->values();

        $selected = $scored->take($query->limit)->pluck('translation');

        if ($selected->count() < $query->limit) {
            $selected = $selected->concat($this->popularFallback(
                $query,
                excludeArticleIds: $selected->pluck('article_id')->push($article->id)->all(),
                remaining: $query->limit - $selected->count(),
            ));
        }

        return $selected->values();
    }

    /**
     * §5.1/§5.2 — ứng viên hợp lệ: cùng locale, published, không phải chính bài đang đọc, không
     * phải format=redirect, và thoả ít nhất 1 trong 3 điều kiện (cùng category/tag/đồng-xem).
     */
    private function candidatePool(
        GetRelatedArticlesQuery $query,
        int $sourceArticleId,
        array $sourceCategoryIds,
        array $sourceTagIds,
        array $coOccurringArticleIds,
    ): Collection {
        $poolLimit = (int) config('post.related_posts.candidate_pool_limit', 200);

        return PostArticleTranslation::published()
            ->where('locale', $query->locale)
            ->where('article_id', '!=', $sourceArticleId)
            ->whereHas('article', function ($q) use ($sourceCategoryIds, $sourceTagIds, $coOccurringArticleIds) {
                $q->where('format', '!=', ArticleFormat::Redirect->value)
                    ->where(function ($sub) use ($sourceCategoryIds, $sourceTagIds, $coOccurringArticleIds) {
                        if ($sourceCategoryIds !== []) {
                            $sub->orWhereHas('categories', fn ($c) => $c->whereIn('post_categories.id', $sourceCategoryIds));
                        }
                        if ($sourceTagIds !== []) {
                            $sub->orWhereHas('tags', fn ($t) => $t->whereIn('post_tags.id', $sourceTagIds));
                        }
                        if ($coOccurringArticleIds !== []) {
                            $sub->orWhereIn('id', $coOccurringArticleIds);
                        }
                    });
            })
            ->with(['article.categories', 'article.tags'])
            ->limit($poolLimit)
            ->get();
    }

    /**
     * §5.3 — self-join: visitor_hash nào đã xem $articleId, họ CÒN xem bài nào khác trong cùng
     * cửa sổ thời gian → đếm số visitor_hash riêng biệt cho mỗi bài "cùng xem".
     *
     * @return Collection<int, int> [article_id => số lượt đồng-xem]
     */
    private function coOccurringArticleIds(int $articleId): Collection
    {
        $lookbackDays = (int) config('post.related_posts.behavior_lookback_days', 90);
        $since        = now()->subDays($lookbackDays);

        return DB::table('post_article_view_events as e1')
            ->join('post_article_view_events as e2', 'e1.visitor_hash', '=', 'e2.visitor_hash')
            ->where('e1.article_id', $articleId)
            ->where('e2.article_id', '!=', $articleId)
            ->where('e1.viewed_at', '>=', $since)
            ->where('e2.viewed_at', '>=', $since)
            ->select('e2.article_id')
            ->selectRaw('COUNT(DISTINCT e1.visitor_hash) as co_views')
            ->groupBy('e2.article_id')
            ->orderByDesc('co_views')
            ->limit(50)
            ->pluck('co_views', 'e2.article_id');
    }

    /**
     * §5.4 — tổng điểm = category + tag + hành vi đồng-xem + độ phổ biến (log scale). Truy cập
     * pivot `is_primary` qua `->pivot` trên từng model đã eager-load (KHÔNG dùng `wherePivot()`
     * — method đó chỉ tồn tại trên query builder BelongsToMany, không có trên Collection đã
     * resolve, gọi trên Collection sẽ ném BadMethodCallException).
     */
    private function score(
        PostArticleTranslation $candidate,
        array $sourceCategoryIds,
        array $sourceTagIds,
        Collection $coOccurrenceCounts,
        array $weights,
    ): float {
        $candidateArticle = $candidate->article;

        $sharedCategoryIds = $candidateArticle->categories->pluck('id')->intersect($sourceCategoryIds);
        $hasPrimaryMatch   = $candidateArticle->categories
            ->filter(fn ($category) => (bool) $category->pivot->is_primary)
            ->pluck('id')
            ->intersect($sourceCategoryIds)
            ->isNotEmpty();

        $categoryScore = match (true) {
            $hasPrimaryMatch                => $weights['category_primary'],
            $sharedCategoryIds->isNotEmpty() => $weights['category_secondary'],
            default                          => 0,
        };

        $tagMatches = min(
            $weights['tag_match_cap'],
            $candidateArticle->tags->pluck('id')->intersect($sourceTagIds)->count(),
        );
        $tagScore = $tagMatches * $weights['tag_per_match'];

        $coViews       = (int) ($coOccurrenceCounts[$candidateArticle->id] ?? 0);
        $behaviorScore = min($coViews, $weights['behavior_covisit_cap']) * $weights['behavior_per_covisit'];

        $popularityScore = log10(1 + $candidate->view_count) * $weights['popularity'];

        return $categoryScore + $tagScore + $behaviorScore + $popularityScore;
    }

    /**
     * §5.5 — lấp chỗ trống bằng bài published phổ biến nhất khi pool có điểm rỗng/thiếu, đảm
     * bảo khối "Bài viết liên quan" không bao giờ trống (trừ khi toàn site chỉ có 1 bài
     * published). Điểm các bài bổ sung này = 0, không giả vờ là "liên quan".
     *
     * @return Collection<int, PostArticleTranslation>
     */
    private function popularFallback(GetRelatedArticlesQuery $query, array $excludeArticleIds, int $remaining): Collection
    {
        if ($remaining <= 0) {
            return collect();
        }

        return PostArticleTranslation::published()
            ->where('locale', $query->locale)
            ->whereNotIn('article_id', $excludeArticleIds)
            ->whereHas('article', fn ($q) => $q->where('format', '!=', ArticleFormat::Redirect->value))
            ->with(['article.categories', 'article.tags'])
            ->orderByDesc('view_count')
            ->limit($remaining)
            ->get();
    }
}
