<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Models\KcItem;

class DestroyKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem): string
    {
        $title = $kcItem->title;
        $kcItem->delete();
        return $title;
    }
}
