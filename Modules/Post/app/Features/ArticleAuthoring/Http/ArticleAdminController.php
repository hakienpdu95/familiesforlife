<?php

namespace Modules\Post\Features\ArticleAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\DeleteArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishAllTranslationsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
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

    /** Chỉ tạo "vỏ" PostArticle — chưa có translation nào, redirect sang edit để tạo bản dịch đầu tiên (§9). */
    public function store(Request $request, CreateArticleAction $action): RedirectResponse
    {
        $this->authorize('create', PostArticle::class);

        $data = ArticleData::from($this->validated($request));
        $article = $action->handle($data);

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', 'Đã tạo bài viết (nháp) — tạo bản dịch đầu tiên bên dưới.');
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
        ]);
    }
}
