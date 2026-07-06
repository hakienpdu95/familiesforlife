<?php

namespace Modules\Branch\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Branch\Models\Branch;

class DestroyBranchAction
{
    use AsAction;

    public function handle(Branch $branch): string
    {
        $name = $branch->name;
        $branch->delete();

        return $name;
    }
}
