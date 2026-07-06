<?php

namespace Modules\KcItem\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\KcItem\Events\KcItemUpdated;

class LogKcItemUpdated
{
    public function handle(KcItemUpdated $event): void
    {
        $item = $event->kcItem;
        ActivityLogger::info('KcItem', 'kc_item_updated', $item, [
            'organization_id' => $item->organization_id,
            'changed_fields'  => implode(',', array_keys($item->getChanges())),
        ]);
    }
}
