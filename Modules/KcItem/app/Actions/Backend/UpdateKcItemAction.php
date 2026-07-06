<?php

namespace Modules\KcItem\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Data\Requests\UpdateKcItemData;
use Modules\KcItem\Events\KcItemUpdated;
use Modules\KcItem\Models\KcItem;

class UpdateKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem, UpdateKcItemData $data): KcItem
    {
        $kcItem->update([
            'category_id'    => $data->category_id,
            'domain_code'    => $data->domain_code,
            'difficulty'     => $data->difficulty,
            'title'          => $data->title,
            'slug'           => $data->slug,
            'summary'        => $data->summary,
            'content'        => $data->content,
            'type'           => $data->type,
            'visibility'     => $data->visibility,
            'language'       => $data->language,
            'is_featured'    => $data->is_featured,
            'is_pinned'      => $data->is_pinned,
            'effective_date' => $data->effective_date,
            'expired_date'   => $data->expired_date,
            'updated_by'     => auth()->id(),
        ]);

        $kcItem->tags()->sync($data->tags);

        event(new KcItemUpdated($kcItem));

        return $kcItem;
    }
}
