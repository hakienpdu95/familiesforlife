<?php

namespace Modules\Post\Features\BreakingNews\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Post\Models\PostBreakingNews;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §7.4 — endpoint polling JSON công khai,
 * dùng lại đúng PostBreakingNews::currentList() (cùng 1 nguồn sự thật với lần render đầu ở
 * PublicCategoryController::index(), tránh lệch kết quả giữa server-render và polling).
 */
class BreakingNewsPublicController extends Controller
{
    public function current(): JsonResponse
    {
        $items = PostBreakingNews::currentList((int) config('post.breaking_news.max_ticker_items', 8));

        return response()->json([
            'items' => $items->map(fn (PostBreakingNews $n) => [
                'headline' => $n->displayHeadline(),
                'url'      => $n->publicTranslation()
                    ? route('post.public.article', ['slug' => $n->publicTranslation()->slug, 'id' => $n->publicTranslation()->id])
                    : null,
            ])->filter(fn (array $item) => $item['url'] !== null)->values(),
        ]);
    }
}
