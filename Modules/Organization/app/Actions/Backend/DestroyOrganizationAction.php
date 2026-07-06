<?php

namespace Modules\Organization\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Models\Organization;

class DestroyOrganizationAction
{
    use AsAction;

    public function handle(Organization $organization): string
    {
        $name = $organization->name;
        $organization->delete();

        return $name;
    }
}
