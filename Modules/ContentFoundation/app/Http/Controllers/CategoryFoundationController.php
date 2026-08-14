<?php

namespace Modules\ContentFoundation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\ContentFoundation\Actions\ListCategoryExistingArticlesAction;
use Modules\ContentFoundation\Actions\ListCategoryFoundationsAction;
use Modules\ContentFoundation\Actions\UpsertCategoryFoundationAction;
use Modules\ContentFoundation\Data\CategoryFoundationData;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 — tách từ CoreIdeaExtractor, giờ dùng chung bởi mọi module nghiên
 * cứu ý tưởng nội dung theo category (CoreIdeaExtractor, VideoIdeaExtractor...). list()/
 * existingArticles() KHÔNG có check bổ sung ngoài middleware 'can:content_foundation.use' đã áp ở
 * routes/web.php (dữ liệu không nhạy cảm, mọi người có quyền dùng cần thấy để chọn ở form trích
 * xuất) — chỉ upsert() mới cần Gate::authorize theo TỪNG category.
 */
class CategoryFoundationController extends Controller
{
    public function index(): View
    {
        return view('contentfoundation::index', [
            'staleAfterDays' => (int) config('content_foundation.foundation.stale_after_days', 180),
        ]);
    }

    public function list(ListCategoryFoundationsAction $listCategoryFoundations): JsonResponse
    {
        return response()->json(['categories' => $listCategoryFoundations->handle()]);
    }

    /**
     * Foundation ĐẦY ĐỦ của ĐÚNG 1 category — CoreIdeaExtractor/VideoIdeaExtractor gọi endpoint này
     * khi người dùng chọn 1 category (applyCategoryFoundation() ở index.blade.php của 2 module đó),
     * vì list() ở trên chỉ trả bản RÚT GỌN cho 2 module này (xem docblock
     * ListCategoryFoundationsAction::handle()) — cùng nguyên tắc "fetch riêng theo yêu cầu" đã áp
     * dụng cho existingArticles() ngay dưới đây.
     */
    public function show(PostCategory $category): JsonResponse
    {
        $foundation = CategoryContentFoundation::query()
            ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $category->id))
            ->with(['categories' => function ($q) {
                $q->where('is_active', true)->select('post_categories.id', 'post_categories.uuid', 'post_categories.name');
            }])
            ->first();

        return response()->json([
            'category_id' => $category->id,
            'foundation' => $foundation?->toDetailArray($category->id),
        ]);
    }

    /**
     * spec/CoreIdeaExtractor.md §12.8 — tiêu đề bài ĐÃ publish trong category, fetch RIÊNG theo
     * yêu cầu (không nhét sẵn vào list() ở trên) vì mỗi category có thể có hàng chục/hàng trăm
     * bài — nhét sẵn cho MỌI category ngay lúc tải trang sẽ phình payload ban đầu không cần thiết.
     */
    public function existingArticles(PostCategory $category, ListCategoryExistingArticlesAction $listExistingArticles): JsonResponse
    {
        return response()->json(['titles' => $listExistingArticles->handle($category)]);
    }

    /**
     * spec/CoreIdeaExtractor.md §12.9 (N-N) — `category_uuids` là tập ĐẦY ĐỦ các category KHÁC
     * (ngoài `$category`) sẽ dùng chung bộ tiêu chí này sau khi lưu. Authorize riêng TỪNG category
     * bị ảnh hưởng thật sự (thêm vào NHÓM lẫn bị gỡ khỏi nhóm hiện tại của `$category`) — không chỉ
     * `$category` — vì section_editor chỉ được quản lý category mình được gán qua
     * postCategoryEditorships(), không nên đổi được liên kết chia sẻ của category người khác quản
     * lý chỉ vì tick/bỏ tick 1 checkbox trên form của `$category`.
     */
    public function upsert(Request $request, PostCategory $category, UpsertCategoryFoundationAction $upsert): JsonResponse
    {
        Gate::authorize('content_foundation.manage_category_foundation', $category);

        // Danh sách key hợp lệ đọc ĐỘNG từ config('content_foundation.family_values.items') —
        // nguồn sự thật duy nhất, không hardcode lặp lại danh sách 4 giá trị ở đây.
        $validFamilyValueKeys = collect(config('content_foundation.family_values.items', []))
            ->pluck('key')
            ->all();

        // spec/CoreIdeaExtractor.md §12.11 — cùng nguyên tắc, cho 4 cặp quan hệ của Bộ tiêu chí
        // ứng xử trong gia đình.
        $validFamilyConductKeys = collect(config('content_foundation.family_conduct_standards.items', []))
            ->pluck('key')
            ->all();

        $validated = $request->validate([
            'core_focus' => ['nullable', 'string', 'max:2000'],
            'writer_insights' => ['nullable', 'string', 'max:1500'],
            'unique_angle' => ['nullable', 'string', 'max:2000'],
            'content_goals' => ['nullable', 'string', 'max:2000'],
            'pain_points' => ['nullable', 'string', 'max:2000'],
            'objections' => ['nullable', 'string', 'max:2000'],
            'decision_criteria' => ['nullable', 'string', 'max:2000'],
            'family_values_focus' => ['nullable', 'array'],
            'family_values_focus.*' => ['string', 'in:'.implode(',', $validFamilyValueKeys)],
            'family_conduct_focus' => ['nullable', 'array'],
            'family_conduct_focus.*' => ['string', 'in:'.implode(',', $validFamilyConductKeys)],
            'rejected_ideas' => ['nullable', 'string', 'max:2000'],
            'audience' => ['nullable', 'string', 'max:500'],
            'audience_behavior' => ['nullable', 'string', 'max:2000'],
            'constraints' => ['nullable', 'string', 'max:500'],
            'style_sample' => ['nullable', 'string', 'max:3000'],
            'category_uuids' => ['sometimes', 'array'],
            'category_uuids.*' => ['string', 'uuid', 'exists:post_categories,uuid'],
        ]);

        $data = CategoryFoundationData::from($validated);

        $requestedOtherCategories = PostCategory::query()
            ->whereIn('uuid', $validated['category_uuids'] ?? [])
            ->where('id', '!=', $category->id)
            ->get();

        $currentFoundation = CategoryContentFoundation::query()
            ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $category->id))
            ->first();

        $categoriesLosingLink = $currentFoundation
            ? $currentFoundation->categories()
                ->where('post_categories.id', '!=', $category->id)
                ->whereNotIn('post_categories.id', $requestedOtherCategories->pluck('id'))
                ->get()
            : collect();

        foreach ($requestedOtherCategories->merge($categoriesLosingLink) as $affectedCategory) {
            Gate::authorize('content_foundation.manage_category_foundation', $affectedCategory);
        }

        $foundation = $upsert->handle($category, $data, $requestedOtherCategories->pluck('id')->all(), $request->user()->id);

        return response()->json([
            'category_id' => $category->id,
            'foundation' => $foundation->toDetailArray($category->id),
        ]);
    }
}
