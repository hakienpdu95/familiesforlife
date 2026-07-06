<?php

namespace Modules\KcItem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Enums\KcItemType;

class KcItemListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \Modules\KcItem\Models\KcItem $this */
        $type   = $this->type instanceof KcItemType ? $this->type : KcItemType::from($this->type);
        $status = $this->status instanceof KcItemStatus ? $this->status : KcItemStatus::from($this->status);

        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'title'          => $this->title,
            'slug'           => $this->slug,
            'type'           => $type->value,
            'type_label'     => $type->label(),
            'type_icon'      => $type->icon(),
            'status'         => $status->value,
            'status_label'   => $status->label(),
            'status_color'   => $status->color(),
            'visibility'     => $this->visibility instanceof \Modules\KcItem\Enums\KcItemVisibility
                                    ? $this->visibility->value : $this->visibility,
            'view_count'     => (int) $this->view_count,
            'version'        => (int) $this->version,
            'is_featured'    => (bool) $this->is_featured,
            'is_pinned'      => (bool) $this->is_pinned,
            'summary'        => $this->summary,

            'category' => $this->category ? [
                'id'        => $this->category->id,
                'name'      => $this->category->name,
                'color_hex' => $this->category->color_hex,
            ] : null,

            'owner' => $this->owner ? [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ] : null,

            'created_at' => $this->created_at?->format('d/m/Y'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

            'show_url'   => route('backend.kc-items.show', $this->resource),
            'edit_url'   => route('backend.kc-items.edit', $this->resource),
            'delete_url' => route('backend.kc-items.destroy', $this->resource),
        ];
    }
}
