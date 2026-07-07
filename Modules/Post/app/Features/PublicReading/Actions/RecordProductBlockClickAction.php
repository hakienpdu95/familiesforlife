<?php

namespace Modules\Post\Features\PublicReading\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostProductBlockButton;
use Modules\Product\Contracts\ProductCatalogContract;

class RecordProductBlockClickAction
{
    use AsAction;

    public function __construct(private readonly ProductCatalogContract $productCatalog) {}

    /** @return string|null URL đích để redirect tới, null nếu không resolve được (link rỗng/đã gỡ). */
    public function handle(PostProductBlockButton $button): ?string
    {
        return DB::transaction(function () use ($button) {
            $button->increment('click_count');

            $targetUrl = $button->resolveTargetUrl();

            $productId = $button->blockItem?->product_id;
            if ($productId) {
                $this->productCatalog->incrementClickCount($productId);
            }

            return $targetUrl;
        });
    }
}
