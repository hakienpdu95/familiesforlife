<?php

namespace Modules\KcItem\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\KcItem\Events\KcItemCreated;

class LogKcItemCreated
{
    public function handle(KcItemCreated $event): void
    {
        $item = $event->kcItem;
        ActivityLogger::info('KcItem', 'kc_item_created', $item, [
            'organization_id' => $item->organization_id,
            'category_id'     => $item->category_id ?? null,
        ]);
    }
}
