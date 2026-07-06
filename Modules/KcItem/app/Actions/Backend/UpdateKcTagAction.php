<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Data\Requests\UpdateKcTagData;
use Modules\KcItem\Models\KcTag;

class UpdateKcTagAction
{
    use AsAction;

    public function handle(KcTag $kcTag, UpdateKcTagData $data): KcTag
    {
        $kcTag->update([
            'name'      => $data->name,
            'slug'      => $data->slug,
            'color_hex' => $data->color_hex,
        ]);

        return $kcTag;
    }
}
