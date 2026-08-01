<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Models\CategoryContentFoundation;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4). Dùng GetCategoryTreeHandler (Post::CategoryManagement)
 * để dựng cây chuyên mục AN TOÀN ở MỌI độ sâu (1 query + group theo parent_id, không N+1/lazy-
 * load) — KHÔNG dùng PostCategory::navTree(), method đó chỉ eager-load ĐÚNG 1 cấp con (thiết kế
 * cho nav 2 cấp của trang chủ), gây LazyLoadingViolationException khi flatten() đệ quy xuống
 * cấp cháu trở đi. flatten() (PostCategory) vẫn dùng được bình thường sau khi có cây đầy đủ.
 */
class ListCategoryFoundationsAction
{
    use AsAction;

    /**
     * @return array<int, array{category_id:int, uuid:string, name:string, depth:int, foundation: array{core_focus:?string, writer_insights:?string, unique_angle:?string, content_goals:?string, pain_points:?string, objections:?string, decision_criteria:?string, family_values_focus: string[], rejected_ideas:?string, audience:?string, constraints:?string, style_sample:?string, updated_at:?string, shared_with: array<int, array{uuid:string, name:string}>}|null}>
     */
    public function handle(): array
    {
        $tree = (new GetCategoryTreeHandler())->handle(new GetCategoryTreeQuery(activeOnly: true));

        // Theo yêu cầu người dùng (2026-07-28) — đồng bộ danh sách trang Content Foundation theo
        // Menu chính (dashboard/menu/items, location=header): dự án có 88 post_categories nhưng
        // 44 category là taxonomy CŨ đã bị thay bằng cấu trúc menu mới (2026-07-27), không còn ai
        // dùng để điều hướng — hiện TOÀN BỘ cây khiến trang lẫn lộn category "sống" (đang lên nav
        // thật) với category "chết" không ai truy cập. Chỉ giữ category (và tổ tiên của nó, để cây
        // không bị đứt gãy) đang được ÍT NHẤT 1 `menu_items.location=header` trỏ tới qua
        // `category_id` — ĐÚNG như điều kiện người dùng chỉ định, không tự thêm ngoại lệ theo số
        // bài viết (dù 8/44 category "chết" vẫn còn bài viết thật — nếu cần lại category đó, gắn nó
        // vào Menu chính là đủ để category-foundations tự thấy lại, không cần đổi code này).
        $referencedCategoryIds = MenuItem::query()
            ->where('location', 'header')
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->all();

        $tree = $this->pruneToMenuReferenced($tree, $referencedCategoryIds);

        $flat = PostCategory::flatten($tree);

        // 1 query cho toàn bộ foundation + category liên kết (§12.9, N-N) — tránh N+1 khi build
        // `shared_with` cho từng category (số lượng foundation thường nhỏ so với số category).
        // Lọc is_active=true giống hệt GetCategoryTreeQuery(activeOnly: true) ở trên — is_active
        // KHÔNG phải global scope như SoftDeletes (category bị soft-delete đã tự động biến mất
        // khỏi quan hệ categories() nhờ global scope, không cần lọc tay), nên nếu không lọc ở đây,
        // 1 category bị TẮT is_active (ẩn khỏi cây chọn) vẫn "ma" xuất hiện trong `shared_with` của
        // category khác đang dùng chung — không đồng bộ với những gì cây bên trái đang hiển thị.
        $foundationByCategoryId = [];
        foreach (CategoryContentFoundation::query()->with(['categories' => function ($q) {
            $q->where('is_active', true)->select('post_categories.id', 'post_categories.uuid', 'post_categories.name');
        }])->get() as $foundation) {
            foreach ($foundation->categories as $linkedCategory) {
                $foundationByCategoryId[$linkedCategory->id] = $foundation;
            }
        }

        return array_map(function (array $node) use ($foundationByCategoryId): array {
            /** @var PostCategory $category */
            $category   = $node['category'];
            $foundation = $foundationByCategoryId[$category->id] ?? null;

            return [
                'category_id' => $category->id,
                'uuid'        => $category->uuid,
                'name'        => $category->name,
                'depth'       => $node['depth'],
                'foundation'  => $foundation ? [
                    'core_focus'      => $foundation->core_focus,
                    'writer_insights' => $foundation->writer_insights,
                    'unique_angle'   => $foundation->unique_angle,
                    'content_goals'  => $foundation->content_goals,
                    'pain_points'    => $foundation->pain_points,
                    'objections'     => $foundation->objections,
                    'decision_criteria' => $foundation->decision_criteria,
                    'family_values_focus' => $foundation->family_values_focus ?? [],
                    'rejected_ideas' => $foundation->rejected_ideas,
                    'audience'       => $foundation->audience,
                    'constraints'    => $foundation->constraints,
                    'style_sample'   => $foundation->style_sample,
                    // Trước đây có sẵn ở DB (timestamps()) nhưng chưa từng lộ ra API/UI — editor
                    // không có cách nào biết 1 foundation đã bao lâu chưa được ôn lại (context
                    // engineering: ngữ cảnh cần được xem là tài sản SỐNG, không phải cấu hình tĩnh
                    // viết 1 lần rồi bỏ quên — xem cảnh báo "stale" ở category-foundations.blade.php).
                    'updated_at'     => $foundation->updated_at?->toIso8601String(),
                    // §12.9 — các category KHÁC (ngoài category hiện tại) đang dùng chung đúng bộ
                    // tiêu chí này, để UI hiển thị "Dùng chung với: ..." và tiền chọn multi-select.
                    'shared_with'    => $foundation->categories
                        ->reject(fn (PostCategory $linked) => $linked->id === $category->id)
                        ->map(fn (PostCategory $linked) => ['uuid' => $linked->uuid, 'name' => $linked->name])
                        ->values()
                        ->all(),
                ] : null,
            ];
        }, $flat);
    }

    /**
     * Giữ node nếu chính nó nằm trong `$referencedCategoryIds`, HOẶC còn ít nhất 1 nhánh con (sau
     * khi đệ quy lọc) — trường hợp sau xảy ra khi 1 category "cha" không tự có `category_id` nào
     * trong Menu chính nhưng vẫn cần hiển thị để giữ category "con" (đang được menu tham chiếu)
     * đúng vị trí trong cây, thay vì để con bị "mồ côi"/đổi lệch độ sâu khi hiển thị.
     *
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
