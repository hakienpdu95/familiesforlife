<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;

class CreateProductAction
{
    use AsAction;

    public function handle(ProductData $data): Product
    {
        return Product::create([
            'category_id'            => $data->category_id,
            'name'                   => $data->name,
            'slug'                   => $this->uniqueSlug($data->name),
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
            'created_by'             => auth()->id(),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
