<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ProductStatus;
use Modules\OcopRubric\Models\OcopProduct;

/** Chuyển trạng thái sang "archived" — khác soft delete: vẫn giữ lại, chỉ đánh dấu ngừng theo dõi. */
class ArchiveProductAction
{
    use AsAction;

    public function handle(OcopProduct $product): OcopProduct
    {
        $product->update(['status' => ProductStatus::Archived->value]);

        return $product->fresh();
    }
}
