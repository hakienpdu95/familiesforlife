<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Data\Requests\StoreKcTagData;
use Modules\KcItem\Models\KcTag;

class StoreKcTagAction
{
    use AsAction;

    public function handle(StoreKcTagData $data): KcTag
    {
        return KcTag::create([
            'organization_id' => $data->organization_id,
            'uuid'      => Str::uuid(),
            'name'      => $data->name,
            'slug'      => $data->slug,
            'color_hex' => $data->color_hex,
        ]);
    }
}
