<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;

class DeleteOcopProductAction
{
    use AsAction;

    /**
     * spec/Media_Library_Technical_Specification.md §7.4 — ảnh sản phẩm nay qua Media, đi theo
     * hành vi mặc định của Spatie (`InteractsWithMedia::bootInteractsWithMedia()`): soft-delete
     * (đây, `$product->delete()`) giữ nguyên ảnh — khôi phục sản phẩm thì ảnh vẫn còn; chỉ
     * `forceDelete()` mới thật sự xoá file. Đổi khác hành vi cũ (trước đây xoá file ảnh ngay cả
     * khi chỉ soft-delete) — có chủ đích, nhất quán với cách Post xử lý.
     */
    public function handle(OcopProduct $product): void
    {
        $product->delete();
    }
}
