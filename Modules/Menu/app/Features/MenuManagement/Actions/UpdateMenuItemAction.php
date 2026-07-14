<?php

namespace Modules\Menu\Features\MenuManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Features\MenuManagement\Data\MenuItemData;
use Modules\Menu\Models\MenuItem;

class UpdateMenuItemAction
{
    use AsAction;

    /**
     * Đổi `parent_id` kéo theo phải tính lại `depth` — và nếu mục đang có `children`/
     * `grandchildren`, phải đẩy `depth` của chúng theo (bị giới hạn bởi menu.max_depth,
     * xem CreateMenuItemAction). Cấp tối đa cố định là 3 (§0) nên cascade tối đa 2 tầng,
     * không cần đệ quy không giới hạn.
     */
    public function handle(MenuItem $menuItem, MenuItemData $data): MenuItem
    {
        $depth = 0;

        if ($data->parent_id) {
            $parent = MenuItem::findOrFail($data->parent_id);

            throw_if($parent->id === $menuItem->id, ValidationException::withMessages([
                'parent_id' => 'Không thể chọn chính mục này làm mục cha.',
            ]));

            throw_if($parent->depth >= config('menu.max_depth'), ValidationException::withMessages([
                'parent_id' => 'Menu chỉ hỗ trợ tối đa 3 cấp — không thể đặt mục con vào đây.',
            ]));

            $depth = $parent->depth + 1;
        }

        $originalDepth = $menuItem->depth;

        if ($depth !== $originalDepth) {
            $this->guardCascadeDepth($menuItem, $depth);
        }

        $menuItem->update([
            'location'        => $data->location,
            'parent_id'       => $data->parent_id,
            'depth'           => $depth,
            'label'           => $data->label,
            'icon'            => $data->icon,
            'sort_order'      => $data->sort_order,
            'is_active'       => $data->is_active,
            'open_in_new_tab' => $data->open_in_new_tab,
            'link_type'       => $data->link_type,
            'category_id'     => $data->category_id,
            'url'             => $data->url,
            'updated_by'      => auth()->id(),
        ]);

        if ($depth !== $originalDepth) {
            $this->cascadeDepth($menuItem, $depth);
        }

        return $menuItem;
    }

    /** Chặn trước khi lưu nếu cascade sẽ đẩy cháu (grandchildren) vượt quá menu.max_depth. */
    private function guardCascadeDepth(MenuItem $menuItem, int $newDepth): void
    {
        $hasGrandchildren = $menuItem->children()->whereHas('children')->exists();

        throw_if($hasGrandchildren && $newDepth + 2 > config('menu.max_depth'), ValidationException::withMessages([
            'parent_id' => 'Không thể đổi cấp — mục này đang có cấp cháu, đổi cấp sẽ vượt quá 3 cấp cho phép.',
        ]));
    }

    private function cascadeDepth(MenuItem $menuItem, int $newDepth): void
    {
        foreach ($menuItem->children()->get() as $child) {
            $child->update(['depth' => $newDepth + 1]);

            foreach ($child->children()->get() as $grandchild) {
                $grandchild->update(['depth' => $newDepth + 2]);
            }
        }
    }
}
