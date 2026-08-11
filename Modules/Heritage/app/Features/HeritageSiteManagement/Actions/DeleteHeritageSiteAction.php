<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Heritage\Models\HeritageSite;

class DeleteHeritageSiteAction
{
    use AsAction;

    /**
     * spec/Media_Library_Technical_Specification.md §7.4 — soft-delete (đây, $site->delete())
     * giữ nguyên ảnh; chỉ forceDelete() mới thật sự xoá file — cùng hành vi OcopProduct.
     *
     * spec/Heritage_Technical_Specification.md §3.7 — Event/OcopProduct trỏ tới di tích này dùng
     * nullOnDelete, KHÔNG cascadeOnDelete — soft-delete ở đây không đụng tới cột heritage_site_id
     * của chúng (chỉ forceDelete() DB-level mới kích hoạt nullOnDelete); §5.2 vì vậy bắt buộc mọi
     * nơi hiển thị link ngược phải query qua HeritageSite::published(), không find() thẳng.
     */
    public function handle(HeritageSite $site): void
    {
        $site->delete();
    }
}
