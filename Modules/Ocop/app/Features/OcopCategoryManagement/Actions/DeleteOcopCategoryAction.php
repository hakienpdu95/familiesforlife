<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopCategory;

class DeleteOcopCategoryAction
{
    use AsAction;

    /**
     * @throws \RuntimeException Nếu danh mục còn sản phẩm gán trực tiếp — category_id trên
     * `ocop_products` dùng restrictOnDelete() ở tầng DB (spec §5) nên xoá sẽ ném lỗi FK nếu bỏ
     * qua guard này; kiểm tra trước ở đây để trả thông báo rõ ràng thay vì lộ lỗi SQL — cùng
     * pattern Modules\Event\Features\EventCategoryManagement\Actions\DeleteEventCategoryAction.
     */
    public function handle(OcopCategory $category): void
    {
        if ($category->products()->exists()) {
            throw new \RuntimeException('Không thể xoá danh mục còn sản phẩm gán trực tiếp — hãy chuyển sản phẩm sang danh mục khác trước.');
        }

        $category->delete();
    }
}
