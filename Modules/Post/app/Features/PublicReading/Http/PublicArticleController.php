<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Post\Features\PublicReading\Actions\IncrementArticleViewCountAction;
use Modules\Post\Features\PublicReading\Actions\RecordArticleRedirectClickAction;
use Modules\Post\Features\RelatedPosts\Actions\RecordArticleViewEventAction;
use Modules\Post\Features\RelatedPosts\Queries\GetRelatedArticlesHandler;
use Modules\Post\Features\RelatedPosts\Queries\GetRelatedArticlesQuery;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;
use Modules\Post\Support\ArticleStructuredDataBuilder;
use Modules\Post\Support\NegotiatesMarkdown;

/**
 * Cổng thông tin công khai chỉ phục vụ 1 locale (config('post.default_locale')) — không còn
 * {locale} trong URL, nên bỏ toàn bộ cơ chế fallback/redirect theo locale khác (bản dịch ở
 * locale khác chưa publish thì không có nghĩa gì với route công khai vì không có URL nào
 * public trỏ tới locale đó) — slug không khớp bản dịch locale mặc định đang published → 404.
 *
 * spec/Markdown_Content_Negotiation_Technical_Specification.md v2.1 — CÙNG 1 URL
 * (`{slug}-d{id}.html`) tự chọn representation (HTML/Markdown) theo header `Accept`, đúng nghĩa
 * "content negotiation" chuẩn RFC 9110 (KHÔNG có URL `.md` riêng — xem §0 lý do đổi kiến trúc
 * từ v1.x).
 */
class PublicArticleController extends Controller
{
    use NegotiatesMarkdown;

    public function show(
        Request $request,
        string $slug,
        IncrementArticleViewCountAction $viewAction,
        RecordArticleRedirectClickAction $clickAction,
        RecordArticleViewEventAction $viewEventAction,
        GetRelatedArticlesHandler $relatedHandler,
        ArticleContentRenderer $renderer,
        ArticleStructuredDataBuilder $structuredDataBuilder,
    ): View|RedirectResponse|Response {
        $translation = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->where('slug', $slug)
            ->with([
                'article.categories', 'article.tags', 'article.createdBy.authorProfile',
                // publicEmbed() — trang public không có TenantContext, OrganizationScope mặc định
                // trả rỗng cho Product tenant-scoped (xem Product::scopePublicEmbed()); cần đọc được
                // để ArticleStructuredDataBuilder::buildOffer() phát sinh Offer.price/priceCurrency.
                'contentBlocks.productBlock.items.product' => fn ($q) => $q->publicEmbed(),
                'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
                'contentBlocks.faqBlock.items', 'faqBlocks.items',
                'contentBlocks.howtoBlock.steps', 'howtoBlocks.steps',
            ])
            ->first();

        abort_unless($translation, 404);

        $article = $translation->article;

        // format=redirect — bài không có nội dung riêng, mọi nơi đang link tới route này
        // (article-card/hero/hero-story/category/search...) không cần đổi gì, vẫn trỏ vào
        // đúng route này — chỉ chặn lại NGAY Ở ĐÂY để chuyển thẳng ra redirect_url, thay vì
        // phải sửa href ở từng nơi hiển thị bài viết. Hành vi GIỐNG NHAU bất kể Accept header
        // (spec Markdown negotiation §0) — redirect là quyết định tầng URL (302, không có
        // Content-Type cho body để negotiate), đặt TRƯỚC bước gọi prefersMarkdown().
        if ($article?->isRedirect() && $article->redirect_url) {
            $viewAction->handle($translation);
            $clickAction->handle($article);

            return redirect()->away($article->redirect_url);
        }

        // Nhánh Markdown — tách sớm TRƯỚC khi tính view_count/related (spec §0: request có
        // Accept: text/markdown là tín hiệu TỰ KHAI BÁO rõ ràng "đây là agent/máy", không tính
        // vào view_count/post_article_view_events vốn dùng để đo hành vi ĐỘC GIẢ THẬT — Related
        // Posts engine dựa vào tín hiệu này).
        if ($this->prefersMarkdown($request)) {
            return $this->showMarkdown($translation, $renderer);
        }

        $viewAction->handle($translation);

        // spec/Related_Posts_Engine_Technical_Specification.md §6.1 — ghi nhận hành vi CHỈ khi
        // bài thực sự được đọc (không ghi cho nhánh redirect ở trên, vì redirect rời trang trước
        // khi có "đọc" thật nào diễn ra; không ghi cho nhánh Markdown ở trên, xem lý do trên).
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
        //
        // response()->view() thay vì return view() trực tiếp — cần để gắn thêm header
        // `Vary: Accept` (spec Markdown negotiation §0 — bắt buộc trên CẢ 2 representation để
        // CDN/reverse-proxy/trình duyệt không cache sai giữa 2 dạng theo cùng 1 URL).
        return response()->view('post::public.article', [
            'translation' => $translation,
            'article' => $article,
            'locale' => $translation->locale,
            'content' => $renderer->render($translation),
            'relatedArticles' => $related,
            'canonicalUrl' => $canonicalUrl,
            'structuredData' => $structuredDataBuilder->build($article, $translation, $canonicalUrl),
        ])->header('Vary', 'Accept');
    }

    /**
     * spec/Markdown_Content_Negotiation_Technical_Specification.md §3 — Markdown thuần (không
     * layout/nav/script) tại ĐÚNG cùng 1 URL đang xem. `Vary: Accept` bắt buộc (cùng lý do nhánh
     * HTML). `Cache-Control` (v2.1) là lớp cache KHÁC với `Cache::remember()` trong
     * `renderMarkdownDocument()` — cho CDN/edge/reverse-proxy tự phục vụ mà không cần chạm tới
     * Laravel, 2 lớp không thay thế nhau (nguồn ekamoira.com). Không thêm cho response HTML —
     * ngoài phạm vi sửa đổi hiện có của spec này.
     */
    private function showMarkdown(PostArticleTranslation $translation, ArticleContentRenderer $renderer): Response
    {
        return response($renderer->renderMarkdownDocument($translation), 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Vary', 'Accept')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
