<?php

namespace Modules\Banner\Features\BannerManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Banner\Features\BannerManagement\Data\BannerData;
use Modules\Banner\Models\Banner;

class CreateBannerAction
{
    use AsAction;

    public function handle(BannerData $data): Banner
    {
        return Banner::create([
            'placement'         => $data->placement,
            'target_type'       => $data->target_type,
            'target_value'      => $data->target_value,
            'title'             => $data->title,
            'image_path'        => $data->image_path,
            'image_width'       => $data->image_width,
            'image_height'      => $data->image_height,
            'image_size_bytes'  => $data->image_size_bytes,
            'alt_text'          => $data->alt_text,
            'link_url'          => $data->link_url,
            'open_in_new_tab'   => $data->open_in_new_tab,
            'badge_label'       => $data->badge_label,
            'start_date'        => $data->start_date,
            'end_date'          => $data->end_date,
            'sort_order'        => $data->sort_order,
            'is_active'         => $data->is_active,
            'created_by'        => auth()->id(),
        ]);
    }
}
