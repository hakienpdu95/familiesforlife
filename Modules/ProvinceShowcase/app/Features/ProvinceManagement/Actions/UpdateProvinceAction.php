<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Actions;

use App\Models\Province;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Data\ProvinceData;

class UpdateProvinceAction
{
    use AsAction;

    public function handle(Province $province, ProvinceData $data): Province
    {
        return DB::transaction(function () use ($province, $data) {
            if ($data->is_featured && ! $province->is_featured) {
                $max = (int) config('provinceshowcase.featured_max', 5);

                $featuredCount = Province::query()
                    ->where('is_featured', true)
                    ->whereKeyNot($province->getKey())
                    ->lockForUpdate()
                    ->count();

                if ($featuredCount >= $max) {
                    throw ValidationException::withMessages([
                        'is_featured' => "Chỉ được phép chọn tối đa {$max} tỉnh/thành hiển thị nổi bật.",
                    ]);
                }
            }

            $province->forceFill([
                'short_name' => $data->short_name,
                'order_column' => $data->order_column,
                'is_active' => $data->is_active,
                'is_featured' => $data->is_featured,
            ])->save();

            return $province;
        });
    }
}
