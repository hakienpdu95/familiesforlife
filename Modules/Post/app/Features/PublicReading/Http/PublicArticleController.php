<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use App\Support\I18n\PortalLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Post\Features\PublicReading\Actions\IncrementArticleViewCountAction;
use Modules\Post\Features\PublicReading\Actions\RecordArticleRedirectClickAction;
use Modules\Post\Features\PublicReading\Actions\ResolveArticleCategoryHeaderAction;
use Modules\Post\Features\RelatedPosts\Actions\RecordArticleViewEventAction;
use Modules\Post\Features\RelatedPosts\Queries\GetRelatedArticlesHandler;
use Modules\Post\Features\RelatedPosts\Queries\GetRelatedArticlesQuery;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;
use Modules\Post\Support\ArticleStructuredDataBuilder;
use Modules\Post\Support\NegotiatesMarkdown;

/**
 * Locale phục vụ theo tiền tố URL (spec/I18n_Specification.md §5.5): thiếu bản dịch của locale
 * đang xem thì render bản gốc kèm banner + noindex, không redirect; slug thuộc bản dịch khác → 301.
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
        ResolveArticleCategoryHeaderAction $categoryHeaderAction,
    ): View|RedirectResponse|Response {
        $portal = app(PortalLocale::class);
        $relations = [
            'article.categories', 'article.tags', 'article.createdBy.authorProfile',
            'contentBlocks.productBlock.items.product', 'contentBlocks.productBlock.items.ocopProduct.media',
            'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
            'contentBlocks.faqBlock.items', 'faqBlocks.items',
            'contentBlocks.howtoBlock.steps', 'howtoBlocks.steps',
        ];

        $translation = PostArticleTranslation::published()
            ->where('locale', $portal->current())
            ->where('slug', $slug)
            ->with($relations)
            ->first();

        if (! $translation) {
            $other = PostArticleTranslation::published()
                ->where('slug', $slug)
                ->whereIn('locale', PortalLocale::supported())
                ->orderByRaw('locale = ? desc', [PortalLocale::default()])
                ->first();

            abort_unless($other, 404);

            $sibling = PostArticleTranslation::published()
                ->where('article_id', $other->article_id)
                ->where('locale', $portal->current())
                ->first();

            if ($sibling) {
                return redirect()->route('post.public.article', ['slug' => $sibling->slug, 'id' => $sibling->id], 301);
            }

            if ($portal->isDefault()) {
                return redirect()->to($portal->using($other->locale, fn () => route('post.public.article', ['slug' => $other->slug, 'id' => $other->id])), 301);
            }

            $translation = $other->load($relations);
        }

        $article = $translation->article;

        $portal->markTranslated($translation->locale === $portal->current())
            ->setAlternates($this->alternateUrls($article->id));

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

        $categoryHeader = $categoryHeaderAction->handle($article);

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
            'currentCategory' => $categoryHeader['current'],
            'leftCategory' => $categoryHeader['left'],
            'rightCategories' => $categoryHeader['right'],
        ])->header('Vary', 'Accept');
    }

    private function alternateUrls(int $articleId): array
    {
        $portal = app(PortalLocale::class);

        return PostArticleTranslation::published()
            ->where('article_id', $articleId)
            ->whereIn('locale', PortalLocale::supported())
            ->get(['id', 'locale', 'slug'])
            ->mapWithKeys(fn (PostArticleTranslation $t) => [
                $t->locale => $portal->using($t->locale, fn () => route('post.public.article', ['slug' => $t->slug, 'id' => $t->id])),
            ])
            ->all();
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
        $response = response($renderer->renderMarkdownDocument($translation), 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Content-Language', $translation->locale)
            ->header('Vary', 'Accept')
            ->header('Cache-Control', 'public, max-age=3600');

        if (! app(PortalLocale::class)->isTranslated()) {
            $response->header('X-Robots-Tag', 'noindex');
        }

        return $response;
    }
}
