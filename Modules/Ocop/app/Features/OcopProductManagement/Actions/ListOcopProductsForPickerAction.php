<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.4.1 — danh sách sản phẩm OCOP theo tỉnh/
 * phường-xã, dùng cho picker "Sản phẩm OCOP liên quan" ở form bài viết (Modules/Post,
 * article-form.js) — load động khi người dùng chọn province_code/ward_code trên <x-address-
 * picker>. Ưu tiên lọc theo ward_code (chuyên sâu hơn) nếu có, fallback province_code. Không
 * truyền gì cả → trả mảng rỗng (picker chỉ hiện gợi ý sau khi đã chọn địa phương, tránh danh
 * sách toàn bộ sản phẩm quá dài).
 */
class ListOcopProductsForPickerAction
{
    use AsAction;

    public function handle(?string $provinceCode, ?string $wardCode): Collection
    {
        if (! $provinceCode && ! $wardCode) {
            return collect();
        }

        return OcopProduct::published()
            ->when($wardCode, fn ($q) => $q->forWard($wardCode))
            ->when(! $wardCode && $provinceCode, fn ($q) => $q->forProvince($provinceCode))
            ->orderBy('name')
            ->get(['id', 'name', 'province_name', 'ward_name']);
    }

    public function asController(Request $request): JsonResponse
    {
        return response()->json($this->handle(
            $request->string('province_code')->value() ?: null,
            $request->string('ward_code')->value() ?: null,
        ));
    }
}
