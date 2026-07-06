<?php

namespace Modules\KcCategory\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcCategory\Models\KcCategory;

class DestroyKcCategoryAction
{
    use AsAction;

    public function handle(KcCategory $kcCategory): string
    {
        // Block delete if category has active children
        if ($kcCategory->children()->withTrashed()->exists()) {
            throw new \RuntimeException('Không thể xóa danh mục khi còn danh mục con.');
        }

        // Block delete if category has kc_items (check via DB)
        $hasItems = \DB::table('kc_items')
            ->where('category_id', $kcCategory->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasItems) {
            throw new \RuntimeException('Không thể xóa danh mục khi còn tài liệu bên trong.');
        }

        $name = $kcCategory->name;
        $kcCategory->delete();

        return $name;
    }
}
