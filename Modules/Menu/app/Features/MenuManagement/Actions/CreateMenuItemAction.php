<?php

namespace Modules\Menu\Features\MenuManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Features\MenuManagement\Data\MenuItemData;
use Modules\Menu\Models\MenuItem;

class CreateMenuItemAction
{
    use AsAction;

    /**
     * spec/Menu_Navigation_Technical_Specification.md §5.3 — chốt chặn cuối cho giới hạn 3
     * cấp, độc lập với UI filter ở form (§5.2/§6.2). KHÔNG thay thế UI filter, chỉ bổ sung.
     */
    public function handle(MenuItemData $data): MenuItem
    {
        $depth = 0;

        if ($data->parent_id) {
            $parent = MenuItem::findOrFail($data->parent_id);

            throw_if($parent->depth >= config('menu.max_depth'), ValidationException::withMessages([
                'parent_id' => 'Menu chỉ hỗ trợ tối đa 3 cấp — không thể thêm mục con vào đây.',
            ]));

            $depth = $parent->depth + 1;
        }

        return MenuItem::create([
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
            'created_by'      => auth()->id(),
        ]);
    }
}
