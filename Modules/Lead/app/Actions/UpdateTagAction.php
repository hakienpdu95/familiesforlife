<?php

namespace Modules\Lead\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\Requests\StoreTagData;
use Modules\Lead\Events\TagUpdated;
use Modules\Lead\Models\LeadTagDefinition;

class UpdateTagAction
{
    use AsAction;

    public function handle(LeadTagDefinition $tag, StoreTagData $data): LeadTagDefinition
    {
        throw_if(
            LeadTagDefinition::where('organization_id', $tag->organization_id)
                ->where('name', $data->name)
                ->where('id', '!=', $tag->id)
                ->exists(),
            new \InvalidArgumentException("Tag \"{$data->name}\" đã tồn tại.")
        );

        $tag->update(['name' => $data->name, 'color' => $data->color]);

        event(new TagUpdated($tag));

        return $tag;
    }
}
