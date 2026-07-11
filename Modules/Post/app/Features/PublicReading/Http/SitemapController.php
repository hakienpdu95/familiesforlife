<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Post\Models\PostArticleTranslation;

class SitemapController extends Controller
{
    /** Chỉ liệt kê translation status=published, route theo đúng {locale}/bai-viet/{slug} (§11.2). */
    public function index(): Response
    {
        $translations = PostArticleTranslation::published()
            ->orderBy('updated_at', 'desc')
            ->get(['locale', 'slug', 'updated_at']);

        return response()
            ->view('post::public.sitemap', compact('translations'))
            ->header('Content-Type', 'text/xml');
    }
}
