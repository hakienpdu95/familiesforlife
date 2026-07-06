<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\OcopRubric\Models\OcopProduct;

class ProductRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly OcopProduct $product) {}
}
