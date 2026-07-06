<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ProductStatus;
use Modules\OcopRubric\Features\ProductRegistry\Data\ProductData;
use Modules\OcopRubric\Features\ProductRegistry\Events\ProductRegistered;
use Modules\OcopRubric\Models\OcopProduct;

/**
 * Đăng ký 1 sản phẩm OCOP thật của tổ chức. Không đòi hỏi bộ sản phẩm phải có
 * rubric active ngay lúc đăng ký — guard đó thuộc StartScoringSessionAction
 * (Phase 4), vì tổ chức có thể đăng ký trước rồi chờ system_admin cấu hình
 * rubric sau.
 */
class RegisterProductAction
{
    use AsAction;

    public function handle(ProductData $data): OcopProduct
    {
        $product = OcopProduct::create([
            'product_group_id' => $data->product_group_id,
            'name'             => $data->name,
            'product_code'     => $data->product_code,
            'status'           => ProductStatus::Draft->value,
            'created_by'       => auth()->id(),
        ]);

        ProductRegistered::dispatch($product);

        return $product;
    }
}
