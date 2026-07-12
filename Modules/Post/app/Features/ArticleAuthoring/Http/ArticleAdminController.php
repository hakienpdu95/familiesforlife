<?php

namespace Modules\Post\Features\ArticleAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\SponsorLabel;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\DeleteArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishAllTranslationsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\RemoveSponsorshipAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminHandler;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminQuery;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Post\Models\PostArticle;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * PostArticle (§9) chỉ còn là "vỏ" dùng chung mọi ngôn ngữ (format/cover/categories/tags) —
 * KHÔNG còn title/status nên KHÔNG dùng authorizeResource(PostArticle::class) (Policy giờ
 * thao tác PostArticleTranslation, xem PostArticlePolicy). viewAny/create không nhận model
 * nên vẫn map thẳng qua Policy; show/edit/update/destroy check quyền + ownership trực tiếp.
 */
class ArticleAdminController extends Controller
{
    public function index(Request $request, ListArticlesForAdminHandler $handler, ListCategoriesForAdminHandler $categoryHandler): View
    {
        $this->authorize('viewAny', PostArticle::class);

        $articles = $handler->handle(new ListArticlesForAdminQuery(
            page:       max(1, $request->integer('page', 1)),
            search:     $request->string('q')->value() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            format:     $request->string('format')->value() ?: null,
            status:     $request->string('status')->value() ?: null,
        ));

        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('post::admin.articles.index', compact('articles', 'categories'));
    }

    public function create(ListCategoriesForAdminHandler $categoryHandler): View
    {
        $this->authorize('create', PostArticle::class);

        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('post::admin.articles.create', compact('categories'));
    }

    /**
     * Gộp tạo "vỏ" PostArticle + bản dịch đầu tiên (ngôn ngữ chính) trong CÙNG 1 lượt submit —
     * khôi phục trải nghiệm "1 bước bấm Thêm mới là vào thẳng bài viết sẵn sàng soạn nội dung"
     * trước khi PostArticle tách vỏ đa ngôn ngữ (Publishing Engine), không tạo Action mới mà chỉ
     * gọi nối tiếp 2 Action per-article/per-locale đã có sẵn trong 1 transaction — nếu tạo bản
     * dịch thất bại (vd trùng slug hiếm gặp), toàn bộ rollback, không để lại "vỏ" mồ côi.
     */
    public function store(Request $request, CreateArticleAction $articleAction, CreateTranslationAction $translationAction): RedirectResponse
    {
        $this->authorize('create', PostArticle::class);

        $data = ArticleData::from($this->validated($request));

        // §9 — permission "bật/tắt is_sponsored" tách khỏi post_article.edit thường: chặn ngay
        // cả khi request giả mạo gửi is_sponsored=true dù UI không hiện checkbox (test §14 mục 8).
        if ($data->is_sponsored) {
            abort_unless(auth()->user()->can('post_article.manage_sponsorship'), 403);
        }

        $title = $request->validate(['title' => ['required', 'string', 'max:300']])['title'];

        [$article, $translation] = DB::transaction(function () use ($data, $title, $articleAction, $translationAction) {
            $article     = $articleAction->handle($data);
            $translation = $translationAction->handle($article, $article->main_locale, TranslationData::from(['title' => $title]));

            return [$article, $translation];
        });

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', 'Đã tạo bài viết — tiếp tục hoàn thiện nội dung bên dưới.')
            ->with('active_locale', $translation->locale);
    }

    public function show(PostArticle $article): View
    {
        $this->authorizeArticle($article, 'post_article.view');

        $article->load(['categories', 'tags', 'createdBy:id,name', 'translations.approvedBy:id,name']);

        return view('post::admin.articles.show', compact('article'));
    }

    public function edit(Request $request, PostArticle $article, ListCategoriesForAdminHandler $categoryHandler, ArticleContentRenderer $renderer): View
    {
        $this->authorizeArticle($article, 'post_article.edit');

        $article->load(['categories', 'tags', 'translations.contentBlocks.productBlock.items.product', 'translations.contentBlocks.productBlock.items.buttons', 'translations.contentBlocks.productBlock.buttons']);
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        // Tab locale server-side (không SPA) — mỗi lần đổi tab load lại trang, tránh phải
        // làm cho post-block-composer.js/article-form.js (vốn giả định 1 form/1 composer duy
        // nhất mỗi trang) hỗ trợ nhiều instance cùng lúc.
        $locales = array_keys(config('post.locales'));
        $requestedLocale = $request->query('locale') ?? session('active_locale');
        $activeLocale = in_array($requestedLocale, $locales, true) ? $requestedLocale : $article->main_locale;

        $translation = $article->translation($activeLocale);
        $existingBlocks = $translation ? $renderer->toComposerPayload($translation) : [];

        return view('post::admin.articles.edit', compact('article', 'categories', 'activeLocale', 'translation', 'existingBlocks'));
    }

    public function update(Request $request, PostArticle $article, UpdateArticleAction $action): RedirectResponse
    {
        $this->authorizeArticle($article, 'post_article.edit');

        $data = ArticleData::from($this->validated($request));

        // §9 — gate cả 2 chiều "bật/tắt": đổi is_sponsored (bật mới hoặc tắt bài đang sponsored)
        // đều cần quyền riêng, không chỉ post_article.edit (test §14 mục 8).
        if ($data->is_sponsored || $article->is_sponsored) {
            abort_unless(auth()->user()->can('post_article.manage_sponsorship'), 403);
        }

        $action->handle($article, $data);

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', 'Cập nhật bài viết thành công.');
    }

    public function destroy(PostArticle $article, DeleteArticleAction $action): RedirectResponse
    {
        $this->authorizeArticle($article, 'post_article.delete');

        $action->handle($article);

        return redirect()->route('backend.post.articles.index')
            ->with('success', 'Đã xoá bài viết.');
    }

    public function publishAll(PostArticle $article, PublishAllTranslationsAction $action): RedirectResponse
    {
        $this->authorizeArticle($article, 'post_article.publish');

        $action->handle($article);

        return back()->with('success', 'Đã xuất bản mọi bản dịch sẵn sàng.');
    }

    /**
     * §7.2/§9 — check permission trực tiếp (không qua $this->authorize('manageSponsorship', ...))
     * vì PostArticlePolicy::manageSponsorship() chỉ kiểm tra permission, không dùng đến field nào
     * của PostArticleTranslation — truyền $article->mainTranslation() (có thể null cho bài chưa
     * có bản dịch nào, dù is_sponsored=true đã bật từ card "Cài đặt chung") sẽ khiến Gate không
     * resolve được policy. Check permission thẳng, đúng pattern authorizeArticle() đã có.
     */
    public function removeSponsor(PostArticle $article, RemoveSponsorshipAction $action): RedirectResponse
    {
        abort_unless(auth()->user()->can('post_article.manage_sponsorship'), 403);

        $action->handle($article);

        return back()->with('success', 'Đã gỡ tài trợ khỏi bài viết.');
    }

    /**
     * PostArticle không còn 1 Policy method riêng (Policy giờ thao tác PostArticleTranslation) —
     * check quyền + ownership trực tiếp, giữ đúng logic cũ (chủ bài HOẶC có quyền publish).
     */
    private function authorizeArticle(PostArticle $article, string $permission): void
    {
        $user = auth()->user();

        abort_unless(
            $user->can($permission) && ($article->created_by === $user->id || $user->can('post_article.publish')),
            403,
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'format'                  => ['required', 'in:' . implode(',', array_column(ArticleFormat::cases(), 'value'))],
            'cover_image_url'         => ['nullable', 'string', 'max:500'],
            'is_featured'             => ['boolean'],
            'main_locale'             => ['nullable', 'string', 'in:' . implode(',', array_keys(config('post.locales')))],
            'category_ids'            => ['array'],
            'category_ids.*'          => ['integer', 'exists:post_categories,id'],
            'is_primary_category_id'  => ['nullable', 'integer'],
            'tags'                    => ['nullable', 'string', 'max:500'],

            // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §6.1/§10 — rule string thuần, KHÔNG dựa
            // vào attribute Spatie Data trên ArticleData (không có tác dụng validate thật ở đây).
            'is_sponsored'            => ['boolean'],
            'sponsor_name'            => ['required_if:is_sponsored,1', 'nullable', 'string', 'max:255'],
            'sponsor_logo_url'        => ['nullable', 'string', 'max:500'],
            'sponsor_label'           => ['required_if:is_sponsored,1', 'nullable', Rule::enum(SponsorLabel::class)],
            'campaign_code'           => ['nullable', 'string', 'max:50'],
            'sponsored_start_date'    => ['nullable', 'date'],
            'sponsored_end_date'      => ['nullable', 'date', 'after_or_equal:sponsored_start_date'],
        ]);
    }
}
