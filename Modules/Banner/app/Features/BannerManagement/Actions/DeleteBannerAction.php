<?php

namespace Modules\Banner\Features\BannerManagement\Actions;

use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Banner\Models\Banner;

class DeleteBannerAction
{
    use AsAction;

    public function handle(Banner $banner): void
    {
        $banner->delete();

        // Soft delete bản ghi, nhưng file ảnh vật lý xoá luôn — banner xoá rồi không có màn
        // "khôi phục" nào cần ảnh cũ (khác Media module có thùng rác riêng).
        Storage::disk('public')->delete($banner->image_path);
    }
}
