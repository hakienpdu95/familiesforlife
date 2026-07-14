<?php

namespace Modules\Menu\Features\MenuManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Models\MenuItem;

class ReorderMenuItemsAction
{
    use AsAction;

    /** @param array<int, int> $order [menu_item_id => sort_order] */
    public function handle(array $order): void
    {
        foreach ($order as $menuItemId => $sortOrder) {
            MenuItem::whereKey($menuItemId)->update(['sort_order' => $sortOrder]);
        }
    }
}
