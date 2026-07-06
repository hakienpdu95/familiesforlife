<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;

class SubmitKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem): KcItem
    {
        $kcItem->update([
            'status'     => KcItemStatus::PendingReview->value,
            'updated_by' => auth()->id(),
        ]);

        return $kcItem;
    }
}
