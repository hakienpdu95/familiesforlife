<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;

class DeleteOcopProductAction
{
    use AsAction;

    public function handle(OcopProduct $product): void
    {
        $product->delete();

        // Soft delete bản ghi, nhưng file ảnh vật lý xoá luôn — cùng pattern DeleteBannerAction
        // (không có thùng rác riêng cho ảnh OCOP).
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }
}
