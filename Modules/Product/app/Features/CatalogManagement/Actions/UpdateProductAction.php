<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;

class UpdateProductAction
{
    use AsAction;

    public function handle(Product $product, ProductData $data): Product
    {
        $product->update([
            'category_id'            => $data->category_id,
            'name'                   => $data->name,
            'sku'                    => $data->sku,
            'type'                   => $data->type,
            'short_description'      => $data->short_description,
            'description'            => $data->description,
            'price'                  => $data->price,
            'price_label'            => $data->price_label,
            'currency'               => $data->currency,
            'cover_image_url'        => $data->cover_image_url,
            'status'                 => $data->status,
            'shopee_url'             => $data->shopee_url,
            'tiktok_url'             => $data->tiktok_url,
            'supplier_url'           => $data->supplier_url,
            'supplier_homepage_url'  => $data->supplier_homepage_url,
            'is_featured'            => $data->is_featured,
            'sort_order'             => $data->sort_order,
            'updated_by'             => auth()->id(),
        ]);

        return $product;
    }
}
