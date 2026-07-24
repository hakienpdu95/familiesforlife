<?php

namespace Modules\Post\Features\AuthorHub\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Features\AuthorHub\Queries\ListAuthorArticlesHandler;
use Modules\Post\Features\AuthorHub\Queries\ListAuthorArticlesQuery;
use Modules\Post\Features\AuthorHub\Queries\ListPublicAuthorsHandler;
use Modules\Post\Features\AuthorHub\Queries\ListPublicAuthorsQuery;
use Modules\Post\Features\AuthorHub\Support\AuthorRoleResolver;
use Modules\Post\Models\PostAuthorProfile;

/** spec/Author_Contributor_Hub_Technical_Specification.md §7 — render công khai, KHÔNG cache (§0). */
class AuthorHubPublicController extends Controller
{
    public function index(Request $request, ListPublicAuthorsHandler $handler): View
    {
        $authors = $handler->handle(new ListPublicAuthorsQuery(
            page: max(1, $request->integer('page', 1)),
        ));

        return view('post::public.author-hub.index', compact('authors'));
    }

    public function show(Request $request, PostAuthorProfile $authorProfile, ListAuthorArticlesHandler $handler): View
    {
        $authorProfile->loadMissing('user');

        // §7.3 — 404 nếu is_public=false, không tồn tại, HOẶC user không (còn) isPlatform() (§0 v1.2).
        abort_unless(
            $authorProfile->is_public && $authorProfile->user && AuthorRoleResolver::isEligible($authorProfile->user),
            404
        );

        $articles = $handler->handle(new ListAuthorArticlesQuery(
            userId: $authorProfile->user_id,
            locale: config('post.default_locale'),
            page: max(1, $request->integer('page', 1)),
            perPage: config('post.author_hub.articles_per_page'),
        ));

        return view('post::public.author-hub.show', compact('authorProfile', 'articles'));
    }
}
