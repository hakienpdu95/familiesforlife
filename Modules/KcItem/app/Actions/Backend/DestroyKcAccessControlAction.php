<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Models\KcAccessControl;

class DestroyKcAccessControlAction
{
    use AsAction;

    public function handle(KcAccessControl $accessControl): void
    {
        $accessControl->delete();
    }
}
