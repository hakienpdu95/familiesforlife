<?php

namespace Modules\KcCategory\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\KcCategory\Events\KcCategoryUpdated;

class LogKcCategoryUpdated
{
    public function handle(KcCategoryUpdated $event): void
    {
        ActivityLogger::info('KcCategory', 'kc_category_updated', $event->kcCategory, [
            'kc_category_id'  => $event->kcCategory->id,
            'name'            => $event->kcCategory->name,
            'organization_id' => $event->kcCategory->organization_id,
        ]);
    }
}
