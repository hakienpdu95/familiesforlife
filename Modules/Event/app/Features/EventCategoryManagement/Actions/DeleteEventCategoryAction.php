<?php

namespace Modules\Event\Features\EventCategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Models\EventCategory;

class DeleteEventCategoryAction
{
    use AsAction;

    /**
     * @throws \RuntimeException Nếu danh mục còn danh mục con hoặc còn sự kiện gán trực tiếp —
     * category_id trên `events` dùng restrictOnDelete() ở tầng DB (spec §5.2) nên xoá sẽ ném
     * lỗi FK nếu bỏ qua guard này; kiểm tra trước ở đây để trả thông báo rõ ràng thay vì lộ lỗi SQL.
     */
    public function handle(EventCategory $category): void
    {
        if ($category->children()->exists()) {
            throw new \RuntimeException('Không thể xoá danh mục còn danh mục con — hãy chuyển/xoá danh mục con trước.');
        }

        if ($category->events()->exists()) {
            throw new \RuntimeException('Không thể xoá danh mục còn sự kiện gán trực tiếp — hãy chuyển sự kiện sang danh mục khác trước.');
        }

        $category->delete();
    }
}
