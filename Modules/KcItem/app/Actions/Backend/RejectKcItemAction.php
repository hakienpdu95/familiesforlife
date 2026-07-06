<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Notifications\KcItemRejectedNotification;

class RejectKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem, string $reason): KcItem
    {
        $kcItem->update([
            'status'     => KcItemStatus::Rejected->value,
            'updated_by' => auth()->id(),
        ]);

        // Notify owner
        $kcItem->load('owner');
        if ($kcItem->owner) {
            $kcItem->owner->notify(new KcItemRejectedNotification($kcItem, $reason));
        }

        return $kcItem;
    }
}
