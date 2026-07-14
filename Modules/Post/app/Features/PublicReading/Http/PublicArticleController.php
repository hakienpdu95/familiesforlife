<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Post\Features\PublicReading\Actions\IncrementArticleViewCountAction;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * Cổng thông tin công khai chỉ phục vụ 1 locale (config('post.default_locale')) — không còn
 * {locale} trong URL, nên bỏ toàn bộ cơ chế fallback/redirect theo locale khác (bản dịch ở
 * locale khác chưa publish thì không có nghĩa gì với route công khai vì không có URL nào
 * public trỏ tới locale đó) — slug không khớp bản dịch locale mặc định đang published → 404.
 */
class PublicArticleController extends Controller
{
    public function show(string $slug, IncrementArticleViewCountAction $viewAction, ArticleContentRenderer $renderer): View
    {
        $translation = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->where('slug', $slug)
            ->with([
                'article.categories', 'article.tags',
                'contentBlocks.productBlock.items.product', 'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
            ])
            ->first();

        abort_unless($translation, 404);

        $viewAction->handle($translation);

        return view('post::public.article', [
            'translation' => $translation,
            'article'     => $translation->article,
            'locale'      => $translation->locale,
            'content'     => $renderer->render($translation),
            'categories'  => PostCategory::navTree(),
        ]);
    }
}
