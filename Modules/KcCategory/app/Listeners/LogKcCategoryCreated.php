<?php

namespace Modules\KcCategory\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\KcCategory\Events\KcCategoryCreated;

class LogKcCategoryCreated
{
    public function handle(KcCategoryCreated $event): void
    {
        ActivityLogger::info('KcCategory', 'kc_category_created', $event->kcCategory, [
            'kc_category_id'  => $event->kcCategory->id,
            'name'            => $event->kcCategory->name,
            'slug'            => $event->kcCategory->slug,
            'organization_id' => $event->kcCategory->organization_id,
        ]);
    }
}
