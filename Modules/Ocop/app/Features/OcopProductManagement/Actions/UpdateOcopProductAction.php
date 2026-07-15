<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Models\OcopProduct;

class UpdateOcopProductAction
{
    use AsAction;

    public function handle(OcopProduct $product, OcopProductData $data): OcopProduct
    {
        $provinceName = $data->province_code
            ? Province::where('province_code', $data->province_code)->value('name')
            : null;
        $wardName = $data->ward_code
            ? Ward::where('ward_code', $data->ward_code)->value('name')
            : null;

        $product->update([
            'category_id'       => $data->category_id,
            'name'              => $data->name,
            'star_rating'       => $data->star_rating,
            'description'       => $data->description,
            'province_code'     => $data->province_code,
            'province_name'     => $provinceName,
            'ward_code'         => $data->ward_code,
            'ward_name'         => $wardName,
            'producer_name'     => $data->producer_name,
            'producer_address'  => $data->producer_address,
            // Ảnh giữ nguyên nếu form không upload ảnh mới — cùng pattern UpdateBannerAction.
            'image_path'        => $data->image_path ?? $product->image_path,
            'image_width'       => $data->image_path ? $data->image_width : $product->image_width,
            'image_height'      => $data->image_path ? $data->image_height : $product->image_height,
            'image_size_bytes'  => $data->image_path ? $data->image_size_bytes : $product->image_size_bytes,
            'purchase_url'      => $data->purchase_url,
            'status'            => $data->status,
            'is_featured'       => $data->is_featured,
            'sort_order'        => $data->sort_order,
            'updated_by'        => auth()->id(),
        ]);

        return $product;
    }
}
