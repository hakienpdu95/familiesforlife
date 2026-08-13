<?php

namespace Modules\EntityComparison\Features\EntityManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Models\Entity;

class DeleteEntityAction
{
    use AsAction;

    public function handle(Entity $entity): void
    {
        $entity->delete();
    }
}
