<?php

namespace Modules\Menu\Features\MenuManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Models\MenuItem;

class DeleteMenuItemAction
{
    use AsAction;

    /** @throws \RuntimeException Nếu mục còn mục con — tránh mồ côi cấp con (§5.2). */
    public function handle(MenuItem $menuItem): void
    {
        if ($menuItem->children()->exists()) {
            throw new \RuntimeException('Không thể xoá mục menu còn mục con — hãy chuyển/xoá mục con trước.');
        }

        $menuItem->delete();
    }
}
