<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;

/** restore() gọi save() nên HasQrIdentity tự làm mới snapshot — mã QR đã in quay về trang thật. */
class RestoreOcopProductAction
{
    use AsAction;

    public function handle(OcopProduct $product): OcopProduct
    {
        $product->restore();

        return $product;
    }
}
