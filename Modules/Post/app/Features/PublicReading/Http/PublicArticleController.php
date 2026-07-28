<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Post\Features\PublicReading\Actions\IncrementArticleViewCountAction;
use Modules\Post\Features\PublicReading\Actions\RecordArticleRedirectClickAction;
use Modules\Post\Features\RelatedPosts\Actions\RecordArticleViewEventAction;
use Modules\Post\Features\RelatedPosts\Queries\GetRelatedArticlesHandler;
use Modules\Post\Features\RelatedPosts\Queries\GetRelatedArticlesQuery;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;
use Modules\Post\Support\ArticleStructuredDataBuilder;

/**
 * Cổng thông tin công khai chỉ phục vụ 1 locale (config('post.default_locale')) — không còn
 * {locale} trong URL, nên bỏ toàn bộ cơ chế fallback/redirect theo locale khác (bản dịch ở
 * locale khác chưa publish thì không có nghĩa gì với route công khai vì không có URL nào
 * public trỏ tới locale đó) — slug không khớp bản dịch locale mặc định đang published → 404.
 */
class PublicArticleController extends Controller
{
    public function show(
        string $slug,
        IncrementArticleViewCountAction $viewAction,
        RecordArticleRedirectClickAction $clickAction,
        RecordArticleViewEventAction $viewEventAction,
        GetRelatedArticlesHandler $relatedHandler,
        ArticleContentRenderer $renderer,
        ArticleStructuredDataBuilder $structuredDataBuilder,
    ): View|RedirectResponse {
        $translation = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->where('slug', $slug)
            ->with([
                'article.categories', 'article.tags', 'article.createdBy.authorProfile',
                'contentBlocks.productBlock.items.product', 'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
                'contentBlocks.faqBlock.items', 'faqBlocks.items',
                'contentBlocks.howtoBlock.steps', 'howtoBlocks.steps',
            ])
            ->first();

        abort_unless($translation, 404);

        $viewAction->handle($translation);

        // format=redirect — bài không có nội dung riêng, mọi nơi đang link tới route này
        // (article-card/hero/hero-story/category/search...) không cần đổi gì, vẫn trỏ vào
        // đúng route này — chỉ chặn lại NGAY Ở ĐÂY để chuyển thẳng ra redirect_url, thay vì
        // phải sửa href ở từng nơi hiển thị bài viết. view_count vẫn tăng ở trên (đóng vai
        // trò tổng số click cộng dồn); ghi thêm 1 dòng post_article_redirect_clicks để có dữ
        // liệu XU HƯỚNG theo ngày cho trang "Thống kê click" (§ArticleAdminController::clicks()).
        $article = $translation->article;
        if ($article?->isRedirect() && $article->redirect_url) {
            $clickAction->handle($article);

            return redirect()->away($article->redirect_url);
        }

        // spec/Related_Posts_Engine_Technical_Specification.md §6.1 — ghi nhận hành vi CHỈ khi
        // bài thực sự được đọc (không ghi cho nhánh redirect ở trên, vì redirect rời trang trước
        // khi có "đọc" thật nào diễn ra).
        $viewEventAction->handle($article->id);

        $related = $relatedHandler->handle(new GetRelatedArticlesQuery(
            articleId: $article->id,
            locale: $translation->locale,
            limit: (int) config('post.related_posts.max_results', 6),
        ));

        $canonicalUrl = route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]);

        // Không còn truyền 'categories' — Phase 3 chuyển nav sang MenuItem::tree() qua View
        // Composer (MenuServiceProvider), public.article.blade.php không tự dùng $categories
        // cho việc gì khác (xem spec/Menu_Navigation_Technical_Specification.md §8 Phase 4).
        return view('post::public.article', [
            'translation'     => $translation,
            'article'         => $article,
            'locale'          => $translation->locale,
            'content'         => $renderer->render($translation),
            'relatedArticles' => $related,
            'canonicalUrl'    => $canonicalUrl,
            'structuredData'  => $structuredDataBuilder->build($article, $translation, $canonicalUrl),
        ]);
    }
}
