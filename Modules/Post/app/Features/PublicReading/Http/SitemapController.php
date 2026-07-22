<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Models\PostArticleTranslation;

class SitemapController extends Controller
{
    /**
     * Chỉ liệt kê bản dịch published CỦA LOCALE MẶC ĐỊNH — route công khai /bai-viet/{slug}
     * không còn {locale}, nên bản dịch ở locale khác (nếu có) không có URL public nào để liệt kê.
     *
     * format=redirect bị loại — URL này chỉ bounce (302) ra domain khác, không có nội dung
     * riêng để Google index, đưa vào sitemap của CHÍNH MÌNH không có ý nghĩa.
     */
    public function index(): Response
    {
        $translations = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->whereHas('article', fn ($q) => $q->where('format', '!=', ArticleFormat::Redirect))
            ->orderBy('updated_at', 'desc')
            ->get(['locale', 'slug', 'updated_at']);

        return response()
            ->view('post::public.sitemap', compact('translations'))
            ->header('Content-Type', 'text/xml');
    }
}
