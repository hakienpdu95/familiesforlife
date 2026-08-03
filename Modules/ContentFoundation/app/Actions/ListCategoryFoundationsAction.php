<?php

namespace Modules\ContentFoundation\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 — tách từ CoreIdeaExtractor. Dùng GetCategoryTreeHandler
 * (Post::CategoryManagement) để dựng cây chuyên mục AN TOÀN ở MỌI độ sâu (1 query + group theo
 * parent_id, không N+1/lazy-load) — KHÔNG dùng PostCategory::navTree(), method đó chỉ eager-load
 * ĐÚNG 1 cấp con, gây LazyLoadingViolationException khi flatten() đệ quy xuống cấp cháu trở đi.
 */
class ListCategoryFoundationsAction
{
    use AsAction;

    /**
     * @param bool $withFoundationDetails Mặc định true — trả TOÀN BỘ field foundation cho MỌI
     *   category (trang quản lý ContentFoundation cần vậy để chuyển qua lại giữa các category tức
     *   thì, không loading mỗi lần click). Truyền false ở CoreIdeaExtractor/VideoIdeaExtractor —
     *   2 module đó chỉ dùng ĐÚNG 1 category/phiên làm việc, tải sẵn full text (tới ~19.500 ký tự
     *   x MỌI category, hiện 52 category) là phí băng thông không cần thiết: chỉ trả bản RÚT GỌN
     *   (CategoryContentFoundation::toHintArray(), 3 field core_focus/unique_angle/rejected_ideas,
     *   đã cắt ngắn) cho MỌI category — đủ cho hint "Bước 0" — còn full detail của category THẬT
     *   SỰ được chọn thì fetch on-demand qua CategoryFoundationController::show() (xem
     *   applyCategoryFoundation() ở index.blade.php của 2 module).
     * @return array<int, array{category_id:int, uuid:string, name:string, depth:int, foundation: array<string, mixed>|null}>
     */
    public function handle(bool $withFoundationDetails = true): array
    {
        $tree = (new GetCategoryTreeHandler())->handle(new GetCategoryTreeQuery(activeOnly: true));

        // Đồng bộ danh sách trang Content Foundation theo Menu chính (dashboard/menu/items,
        // location=header) — chỉ giữ category (và tổ tiên của nó) đang được ÍT NHẤT 1
        // `menu_items.location=header` trỏ tới qua `category_id`, tránh lẫn category "chết" không
        // còn ai dùng để điều hướng.
        $referencedCategoryIds = MenuItem::query()
            ->where('location', 'header')
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->all();

        $tree = $this->pruneToMenuReferenced($tree, $referencedCategoryIds);

        $flat = PostCategory::flatten($tree);

        // 1 query cho toàn bộ foundation + category liên kết (N-N) — tránh N+1 khi build
        // `shared_with` cho từng category. `shared_with` chỉ có ý nghĩa ở bản đầy đủ (toDetailArray)
        // — bản rút gọn (toHintArray) không dùng tới, nên bỏ luôn eager-load + select cột thừa khi
        // $withFoundationDetails=false thay vì tải quan hệ N-N không dùng tới.
        $foundationQuery = CategoryContentFoundation::query();

        if ($withFoundationDetails) {
            $foundationQuery->with(['categories' => function ($q) {
                $q->where('is_active', true)->select('post_categories.id', 'post_categories.uuid', 'post_categories.name');
            }]);
        } else {
            $foundationQuery->select(['id', 'core_focus', 'unique_angle', 'rejected_ideas'])
                ->with(['categories' => function ($q) {
                    $q->select('post_categories.id');
                }]);
        }

        $foundationByCategoryId = [];
        foreach ($foundationQuery->get() as $foundation) {
            foreach ($foundation->categories as $linkedCategory) {
                $foundationByCategoryId[$linkedCategory->id] = $foundation;
            }
        }

        return array_map(function (array $node) use ($foundationByCategoryId, $withFoundationDetails): array {
            /** @var PostCategory $category */
            $category   = $node['category'];
            $foundation = $foundationByCategoryId[$category->id] ?? null;

            return [
                'category_id' => $category->id,
                'uuid'        => $category->uuid,
                'name'        => $category->name,
                'depth'       => $node['depth'],
                'foundation'  => $foundation
                    ? ($withFoundationDetails ? $foundation->toDetailArray($category->id) : $foundation->toHintArray())
                    : null,
            ];
        }, $flat);
    }

    /**
     * @param Collection<int, PostCategory> $nodes
     * @param int[] $referencedCategoryIds
     * @return Collection<int, PostCategory>
     */
    private function pruneToMenuReferenced(Collection $nodes, array $referencedCategoryIds): Collection
    {
        return $nodes
            ->map(function (PostCategory $node) use ($referencedCategoryIds) {
                $node->setRelation('children', $this->pruneToMenuReferenced($node->children, $referencedCategoryIds));

                return $node;
            })
            ->filter(fn (PostCategory $node) => in_array($node->id, $referencedCategoryIds, true) || $node->children->isNotEmpty())
            ->values();
    }
}
