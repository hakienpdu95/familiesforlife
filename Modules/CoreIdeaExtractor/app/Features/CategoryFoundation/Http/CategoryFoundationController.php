<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions\ListCategoryExistingArticlesAction;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions\ListCategoryFoundationsAction;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions\UpsertCategoryFoundationAction;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Data\CategoryFoundationData;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4). Xem/list KHÔNG có check bổ sung ngoài middleware
 * 'can:core_idea_extractor.use' đã áp ở routes/web.php (dữ liệu không nhạy cảm, mọi người có
 * quyền dùng module đều cần thấy để chọn ở form trích xuất) — chỉ upsert() mới cần
 * Gate::authorize theo TỪNG category (xem CoreIdeaExtractorServiceProvider::boot()).
 */
class CategoryFoundationController extends Controller
{
    public function index(): View
    {
        return view('coreideaextractor::category-foundations', [
            'staleAfterDays' => (int) config('core_idea_extractor.foundation.stale_after_days', 180),
        ]);
    }

    public function list(ListCategoryFoundationsAction $listCategoryFoundations): JsonResponse
    {
        return response()->json(['categories' => $listCategoryFoundations->handle()]);
    }

    /**
     * spec/CoreIdeaExtractor.md §12.8 (v1.11) — tiêu đề bài ĐÃ publish trong category, fetch
     * RIÊNG theo yêu cầu (không nhét sẵn vào list() ở trên) vì mỗi category có thể có hàng chục/
     * hàng trăm bài — nhét sẵn cho MỌI category ngay lúc tải trang sẽ phình payload ban đầu không
     * cần thiết cho các category người dùng còn chưa chọn tới.
     */
    public function existingArticles(PostCategory $category, ListCategoryExistingArticlesAction $listExistingArticles): JsonResponse
    {
        return response()->json(['titles' => $listExistingArticles->handle($category)]);
    }

    public function upsert(Request $request, PostCategory $category, UpsertCategoryFoundationAction $upsert): JsonResponse
    {
        Gate::authorize('core_idea_extractor.manage_category_foundation', $category);

        $data = CategoryFoundationData::from($request->validate([
            'core_focus'     => ['nullable', 'string', 'max:2000'],
            'unique_angle'   => ['nullable', 'string', 'max:2000'],
            'content_goals'  => ['nullable', 'string', 'max:2000'],
            'pain_points'    => ['nullable', 'string', 'max:2000'],
            'rejected_ideas' => ['nullable', 'string', 'max:2000'],
            'audience'       => ['nullable', 'string', 'max:500'],
            'constraints'    => ['nullable', 'string', 'max:500'],
            'style_sample'   => ['nullable', 'string', 'max:3000'],
        ]));

        $foundation = $upsert->handle($category, $data, $request->user()->id);

        return response()->json([
            'category_id' => $category->id,
            'foundation'  => [
                'core_focus'     => $foundation->core_focus,
                'unique_angle'   => $foundation->unique_angle,
                'content_goals'  => $foundation->content_goals,
                'pain_points'    => $foundation->pain_points,
                'rejected_ideas' => $foundation->rejected_ideas,
                'audience'       => $foundation->audience,
                'constraints'    => $foundation->constraints,
                'style_sample'   => $foundation->style_sample,
                'updated_at'     => $foundation->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
