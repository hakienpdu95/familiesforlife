<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Models\KcTag;

class DestroyKcTagAction
{
    use AsAction;

    public function handle(KcTag $kcTag): string
    {
        $name = $kcTag->name;
        $kcTag->items()->detach();
        $kcTag->delete();

        return $name;
    }
}
