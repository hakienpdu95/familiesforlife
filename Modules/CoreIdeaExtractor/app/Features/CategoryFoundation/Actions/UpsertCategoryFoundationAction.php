<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Data\CategoryFoundationData;
use Modules\CoreIdeaExtractor\Models\CategoryContentFoundation;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12.9 (N-N) — 1 bộ tiêu chí có thể áp dụng cho nhiều category (bảng
 * nối cie_foundation_categories), nhưng 1 category chỉ dùng ĐÚNG 1 bộ tại 1 thời điểm
 * (unique(post_category_id) ở bảng nối). `$otherCategoryIds` là tập ĐẦY ĐỦ các category KHÁC
 * (ngoài `$category`) mà bộ tiêu chí này sẽ áp dụng chung sau khi lưu — thay thế hoàn toàn tập cũ
 * (sync semantics), category nào đang dùng bộ khác sẽ bị "chuyển nhà" sang bộ này.
 * `created_by` CHỈ set ở lần tạo đầu tiên (không ghi đè khi update lần sau bởi người khác).
 */
class UpsertCategoryFoundationAction
{
    use AsAction;

    /** @param int[] $otherCategoryIds */
    public function handle(PostCategory $category, CategoryFoundationData $data, array $otherCategoryIds, int $userId): CategoryContentFoundation
    {
        return DB::transaction(function () use ($category, $data, $otherCategoryIds, $userId) {
            $foundation = CategoryContentFoundation::query()
                ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $category->id))
                ->first() ?? new CategoryContentFoundation();

            $foundation->fill([
                'core_focus'      => $data->core_focus,
                'writer_insights' => $data->writer_insights,
                'unique_angle'   => $data->unique_angle,
                'content_goals'  => $data->content_goals,
                'pain_points'    => $data->pain_points,
                'objections'     => $data->objections,
                'decision_criteria' => $data->decision_criteria,
                'family_values_focus' => $data->family_values_focus,
                'rejected_ideas' => $data->rejected_ideas,
                'audience'       => $data->audience,
                'constraints'    => $data->constraints,
                'style_sample'   => $data->style_sample,
                'updated_by'     => $userId,
            ]);

            if (! $foundation->exists) {
                $foundation->created_by = $userId;
            }

            $foundation->save();

            $targetCategoryIds = array_values(array_unique([$category->id, ...$otherCategoryIds]));

            // Category đang thuộc 1 bộ tiêu chí KHÁC nhưng nằm trong target set → "chuyển nhà" (gỡ
            // liên kết cũ trước, vì unique(post_category_id) không cho phép 1 category thuộc 2 bộ).
            $vacatedFoundationIds = CategoryContentFoundation::query()
                ->whereHas('categories', fn ($q) => $q->whereIn('post_categories.id', $targetCategoryIds))
                ->where('id', '!=', $foundation->id)
                ->pluck('id');

            DB::table('cie_foundation_categories')
                ->whereIn('post_category_id', $targetCategoryIds)
                ->where('foundation_id', '!=', $foundation->id)
                ->delete();

            $foundation->categories()->sync($targetCategoryIds);

            // Bộ tiêu chí cũ có thể trở thành "mồ côi" (0 category) sau khi các category của nó bị
            // chuyển hết sang bộ này, hoặc do $category tự tách khỏi 1 bộ dùng chung — dọn rác luôn
            // thay vì để lại bản ghi vô nghĩa không ai còn trỏ tới.
            CategoryContentFoundation::query()
                ->whereIn('id', $vacatedFoundationIds)
                ->whereDoesntHave('categories')
                ->delete();

            // is_active=false không phải global scope (khác SoftDeletes) — lọc tay để category bị
            // TẮT hoạt động không "ma" xuất hiện trong `shared_with` trả về (xem cùng lý do ở
            // ListCategoryFoundationsAction).
            return $foundation->load(['categories' => function ($q) {
                $q->where('is_active', true)->select('post_categories.id', 'post_categories.uuid', 'post_categories.name');
            }]);
        });
    }
}
