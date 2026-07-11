<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesHandler;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesQuery;
use Modules\Post\Models\PostCategory;

class PublicCategoryController extends Controller
{
    public function index(Request $request, string $locale, ListPublishedArticlesHandler $handler): View
    {
        abort_unless(array_key_exists($locale, config('post.locales')), 404);

        $articles = $handler->handle(new ListPublishedArticlesQuery(
            locale: $locale,
            page: max(1, $request->integer('page', 1)),
        ));

        $categories = PostCategory::active()->root()->orderBy('sort_order')->get();

        return view('post::public.home', compact('articles', 'categories', 'locale'));
    }

    public function show(Request $request, string $locale, PostCategory $category, ListPublishedArticlesHandler $handler): View
    {
        abort_unless(array_key_exists($locale, config('post.locales')), 404);

        $articles = $handler->handle(new ListPublishedArticlesQuery(
            locale: $locale,
            page: max(1, $request->integer('page', 1)),
            categoryId: $category->id,
        ));

        $breadcrumb = collect();
        $node = $category;
        while ($node) {
            $breadcrumb->prepend($node);
            $node = $node->parent;
        }

        $categories = PostCategory::active()->root()->orderBy('sort_order')->get();

        return view('post::public.category', compact('articles', 'category', 'breadcrumb', 'categories', 'locale'));
    }
}
