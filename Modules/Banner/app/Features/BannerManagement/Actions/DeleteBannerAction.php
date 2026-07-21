<?php

namespace Modules\Banner\Features\BannerManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Banner\Models\Banner;

class DeleteBannerAction
{
    use AsAction;

    /**
     * spec/Media_Library_Technical_Specification.md §7.4 — ảnh banner nay qua Media, đi theo
     * hành vi mặc định của Spatie: soft-delete (đây) giữ nguyên ảnh, chỉ `forceDelete()` mới
     * thật sự xoá file. Đổi khác hành vi cũ (trước đây xoá file ảnh ngay cả khi chỉ soft-delete).
     */
    public function handle(Banner $banner): void
    {
        $banner->delete();
    }
}
