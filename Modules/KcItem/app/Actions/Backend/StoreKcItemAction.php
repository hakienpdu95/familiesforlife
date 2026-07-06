<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Data\Requests\StoreKcItemData;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Events\KcItemCreated;
use Modules\KcItem\Models\KcItem;

class StoreKcItemAction
{
    use AsAction;

    public function handle(StoreKcItemData $data): KcItem
    {
        $kcItem = KcItem::create([
            'organization_id' => $data->organization_id,
            'uuid'           => Str::uuid(),
            'category_id'    => $data->category_id,
            'domain_code'    => $data->domain_code,
            'difficulty'     => $data->difficulty,
            'title'          => $data->title,
            'slug'           => $data->slug,
            'summary'        => $data->summary,
            'content'        => $data->content,
            'type'           => $data->type,
            'status'         => KcItemStatus::Draft->value,
            'visibility'     => $data->visibility,
            'language'       => $data->language,
            'is_featured'    => $data->is_featured,
            'is_pinned'      => $data->is_pinned,
            'owner_id'       => auth()->id(),
            'effective_date' => $data->effective_date,
            'expired_date'   => $data->expired_date,
            'created_by'     => auth()->id(),
        ]);

        if (!empty($data->tags)) {
            $kcItem->tags()->sync($data->tags);
        }

        event(new KcItemCreated($kcItem));

        return $kcItem;
    }
}
