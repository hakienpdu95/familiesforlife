<?php

namespace Modules\Department\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Department\Models\Department;

class DestroyDepartmentAction
{
    use AsAction;

    public function handle(Department $dept): string
    {
        $name = $dept->name;
        $dept->delete();

        return $name;
    }
}
