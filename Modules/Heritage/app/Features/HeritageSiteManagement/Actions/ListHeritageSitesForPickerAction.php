<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Heritage\Models\HeritageSite;

/**
 * spec/Heritage_Technical_Specification.md §8.3 — danh sách di tích theo tỉnh/phường-xã, dùng
 * cho picker "Di tích liên quan" ở form bài viết (Modules/Post, article-form.js), mirror 1-1
 * ListOcopProductsForPickerAction. Ưu tiên lọc theo ward_code nếu có, fallback province_code.
 * Không truyền gì cả → trả mảng rỗng (picker chỉ hiện gợi ý sau khi đã chọn địa phương).
 */
class ListHeritageSitesForPickerAction
{
    use AsAction;

    public function handle(?string $provinceCode, ?string $wardCode): Collection
    {
        if (! $provinceCode && ! $wardCode) {
            return collect();
        }

        return HeritageSite::published()
            ->when($wardCode, fn ($q) => $q->where('ward_code', $wardCode))
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
