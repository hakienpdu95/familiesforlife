<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;

class ArchiveKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem): KcItem
    {
        $kcItem->update([
            'status'     => KcItemStatus::Archived->value,
            'updated_by' => auth()->id(),
        ]);

        return $kcItem;
    }
}
