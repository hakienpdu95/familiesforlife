<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Queries;

use App\Models\User;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

/**
 * docs/form-ui-spec.md §22 + Modules/Post/resources/views/admin/categories/create.blade.php —
 * dropdown category phải thể hiện phân cấp cha/con (indentation), không phải danh sách phẳng
 * theo alphabet. Dùng lại đúng GetCategoryTreeHandler + PostCategory::flatten() mà Post module
 * dùng cho chính dropdown "Danh mục cha" của nó — KHÔNG viết lại thuật toán dựng cây.
 *
 * platform_section_editor (không đồng thời là content_editor/head) chỉ được TẠO entry trong
 * category thuộc postCategoryEditorships() (§6.4 CreateCalendarEntryAction) — dropdown vẫn hiện
 * đủ cây để giữ ngữ cảnh phân cấp (biết category mình phụ trách nằm dưới nhánh nào), nhưng node
 * KHÔNG thuộc phạm vi bị đánh dấu `selectable:false` (option disabled ở client) thay vì bị xoá
 * khỏi cây — xoá hẳn sẽ làm gãy thụt lề của chính category họ được chọn (mất tổ tiên).
 */
class ListCategoryTreeOptionsAction
{
    use AsAction;

    /** @return array<int, array{id:int, uuid:string, name:string, depth:int, selectable:bool}> */
    public function handle(User $viewer): array
    {
        $tree = (new GetCategoryTreeHandler())->handle(new GetCategoryTreeQuery(activeOnly: true));
        $flat = PostCategory::flatten($tree);

        $restrictedToIds = $this->restrictedCategoryIds($viewer);

        return array_map(function (array $node) use ($restrictedToIds): array {
            /** @var PostCategory $category */
            $category = $node['category'];

            return [
                'id'         => $category->id,
                'uuid'       => $category->uuid,
                'name'       => $category->name,
                'depth'      => $node['depth'],
                'selectable' => $restrictedToIds === null || in_array($category->id, $restrictedToIds, true),
            ];
        }, $flat);
    }

    /** null = không giới hạn (mọi category chọn được) — chỉ platform_section_editor "thuần" mới bị giới hạn. */
    private function restrictedCategoryIds(User $viewer): ?array
    {
        if (! $viewer->isPlatformSectionEditor() || $viewer->isPlatformContentEditor() || $viewer->isPlatformContentHead()) {
            return null;
        }

        /** @var Collection $ids */
        $ids = $viewer->postCategoryEditorships()->pluck('post_categories.id');

        return $ids->all();
    }
}
