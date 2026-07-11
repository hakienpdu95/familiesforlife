<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\PublicReading\Actions\IncrementArticleViewCountAction;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * §11.1/§11.2 — KHÔNG dùng implicit route-model-binding ({translation:slug}) ở đây dù
 * PostArticleTranslation đã có resolveRouteBinding() override cho field 'slug': implicit
 * binding thất bại sẽ ném ModelNotFoundException NGAY TRONG middleware pipeline, trước khi
 * vào được action — không có cách nào chạy fallback tầng controller (§11.2 bước 2) nếu
 * không đăng ký thêm exception renderer toàn cục (bootstrap/app.php, ảnh hưởng mọi module).
 * Nhận `$slug` dạng string thường và tự resolve — giữ toàn bộ logic 404/redirect trong 1 chỗ.
 */
class PublicArticleController extends Controller
{
    public function show(string $locale, string $slug, IncrementArticleViewCountAction $viewAction, ArticleContentRenderer $renderer): View|RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('post.locales')), 404);

        $translation = PostArticleTranslation::published()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->with([
                'article.categories', 'article.tags',
                'contentBlocks.productBlock.items.product', 'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
            ])
            ->first();

        if ($translation) {
            $viewAction->handle($translation);

            $hreflangs = $translation->article->translations()->published()->get(['locale', 'slug']);

            return view('post::public.article', [
                'translation' => $translation,
                'article'     => $translation->article,
                'hreflangs'   => $hreflangs,
                'locale'      => $locale,
                'content'     => $renderer->render($translation),
            ]);
        }

        return $this->fallback($locale, $slug);
    }

    /**
     * §11.2 bước 2 — slug đúng nhưng sai locale (hoặc bản dịch locale này chưa published):
     * tra theo slug ở BẤT KỲ locale nào đang published → nếu bản main_locale của cùng
     * article cũng đang published, redirect 302 sang đúng locale đó. Không có gì khớp →
     * 404 (bước 3, không fallback sang draft/unpublished cho khách vãng lai).
     */
    private function fallback(string $locale, string $slug): RedirectResponse
    {
        $article = PostArticle::whereHas('translations', function ($q) use ($slug) {
            $q->where('slug', $slug)->where('status', TranslationStatus::Published);
        })->first();

        if ($article) {
            $mainTranslation = $article->translations()
                ->where('locale', $article->main_locale)
                ->where('status', TranslationStatus::Published)
                ->first();

            if ($mainTranslation) {
                return redirect()->route('post.public.article', [
                    'locale' => $mainTranslation->locale,
                    'slug'   => $mainTranslation->slug,
                ], 302);
            }
        }

        abort(404);
    }
}
