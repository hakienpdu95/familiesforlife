<?php

namespace Modules\Lead\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\Requests\StoreTagData;
use Modules\Lead\Events\TagCreated;
use Modules\Lead\Models\LeadTagDefinition;

class CreateTagAction
{
    use AsAction;

    public function handle(StoreTagData $data, int $orgId): LeadTagDefinition
    {
        throw_if(
            LeadTagDefinition::where('organization_id', $orgId)
                ->where('name', $data->name)
                ->exists(),
            new \InvalidArgumentException("Tag \"{$data->name}\" đã tồn tại.")
        );

        $tag = LeadTagDefinition::create([
            'organization_id' => $orgId,
            'name'            => $data->name,
            'color'           => $data->color,
        ]);

        event(new TagCreated($tag));

        return $tag;
    }
}
