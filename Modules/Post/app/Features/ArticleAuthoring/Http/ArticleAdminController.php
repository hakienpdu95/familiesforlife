<?php

namespace Modules\Post\Features\ArticleAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Features\ArticleAuthoring\Actions\ArchiveArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\DeleteArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\ScheduleArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SubmitArticleForReviewAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Exceptions\ProductBlockValidationException;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminHandler;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminQuery;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Post\Models\PostArticle;
use Modules\Post\Support\ArticleContentRenderer;

class ArticleAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PostArticle::class, 'article');
    }

    public function index(Request $request, ListArticlesForAdminHandler $handler, ListCategoriesForAdminHandler $categoryHandler): View
    {
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
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());
        $existingBlocks = [];

        return view('post::admin.articles.create', compact('categories', 'existingBlocks'));
    }

    public function store(Request $request, CreateArticleAction $action): RedirectResponse
    {
        $data = ArticleData::from($this->validated($request));

        try {
            $article = $action->handle($data);
        } catch (ProductBlockValidationException $e) {
            return back()->withInput()->withErrors(['blocks' => $e->errors]);
        }

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', "Bài viết \"{$article->title}\" đã được tạo (nháp).");
    }

    public function show(PostArticle $article): View
    {
        $article->load(['categories', 'tags', 'createdBy:id,name', 'approvedBy:id,name']);

        return view('post::admin.articles.show', compact('article'));
    }

    public function edit(PostArticle $article, ListCategoriesForAdminHandler $categoryHandler, ArticleContentRenderer $renderer): View
    {
        $article->load(['categories', 'tags']);
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());
        $existingBlocks = $renderer->toComposerPayload($article);

        return view('post::admin.articles.edit', compact('article', 'categories', 'existingBlocks'));
    }

    public function update(Request $request, PostArticle $article, UpdateArticleAction $action): RedirectResponse
    {
        $data = ArticleData::from($this->validated($request));

        try {
            $action->handle($article, $data);
        } catch (ProductBlockValidationException $e) {
            return back()->withInput()->withErrors(['blocks' => $e->errors]);
        }

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', 'Cập nhật bài viết thành công.');
    }

    public function destroy(PostArticle $article, DeleteArticleAction $action): RedirectResponse
    {
        $action->handle($article);

        return redirect()->route('backend.post.articles.index')
            ->with('success', "Đã xoá bài viết \"{$article->title}\".");
    }

    public function submit(PostArticle $article, SubmitArticleForReviewAction $action): RedirectResponse
    {
        $this->authorize('submitForReview', $article);
        $action->handle($article);

        return back()->with('success', 'Đã gửi bài viết để chờ duyệt.');
    }

    public function publish(PostArticle $article, PublishArticleAction $action): RedirectResponse
    {
        $this->authorize('publish', $article);
        $action->handle($article);

        return back()->with('success', 'Đã xuất bản bài viết.');
    }

    public function schedule(Request $request, PostArticle $article, ScheduleArticleAction $action): RedirectResponse
    {
        $this->authorize('schedule', $article);
        $validated = $request->validate(['published_at' => ['required', 'date', 'after:now']]);
        $action->handle($article, Carbon::parse($validated['published_at']));

        return back()->with('success', 'Đã lên lịch xuất bản bài viết.');
    }

    public function archive(PostArticle $article, ArchiveArticleAction $action): RedirectResponse
    {
        $this->authorize('archive', $article);
        $action->handle($article);

        return back()->with('success', 'Đã lưu trữ bài viết.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title'                   => ['required', 'string', 'max:300'],
            'format'                  => ['required', 'in:' . implode(',', array_column(ArticleFormat::cases(), 'value'))],
            'excerpt'                 => ['nullable', 'string', 'max:500'],
            'blocks_json'             => ['nullable', 'string'],
            'cover_image_url'         => ['nullable', 'string', 'max:500'],
            'seo_title'               => ['nullable', 'string', 'max:200'],
            'seo_description'        => ['nullable', 'string', 'max:300'],
            'is_featured'             => ['boolean'],
            'category_ids'            => ['array'],
            'category_ids.*'          => ['integer', 'exists:post_categories,id'],
            'is_primary_category_id'  => ['nullable', 'integer'],
            'tags'                    => ['nullable', 'string', 'max:500'],
        ]);

        $blocks = json_decode($validated['blocks_json'] ?? '[]', true);
        $validated['blocks'] = is_array($blocks) ? $blocks : [];
        unset($validated['blocks_json']);

        return $validated;
    }
}
