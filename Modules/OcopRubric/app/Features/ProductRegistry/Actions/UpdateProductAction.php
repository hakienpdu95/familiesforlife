<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Features\ProductRegistry\Data\ProductData;
use Modules\OcopRubric\Models\OcopProduct;

class UpdateProductAction
{
    use AsAction;

    public function handle(OcopProduct $product, ProductData $data): OcopProduct
    {
        $product->update([
            'product_group_id' => $data->product_group_id,
            'name'             => $data->name,
            'product_code'     => $data->product_code,
        ]);

        return $product->fresh();
    }
}
