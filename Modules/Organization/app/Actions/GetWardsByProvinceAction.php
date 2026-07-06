<?php

namespace Modules\Organization\Actions;

use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Trả về danh sách phường/xã theo province_code.
 * Dùng cho API endpoint load động khi chọn tỉnh/thành phố.
 */
class GetWardsByProvinceAction
{
    use AsAction;

    public function handle(string $provinceCode): Collection
    {
        return Ward::where('province_code', $provinceCode)
            ->orderBy('name')
            ->get(['ward_code', 'name', 'place_type']);
    }

    public function asController(string $provinceCode): JsonResponse
    {
        return response()->json($this->handle($provinceCode));
    }
}
