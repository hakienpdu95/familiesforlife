<?php

namespace Modules\Banner\Features\BannerManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Banner\Features\BannerManagement\Data\BannerData;
use Modules\Banner\Models\Banner;

class UpdateBannerAction
{
    use AsAction;

    public function handle(Banner $banner, BannerData $data): Banner
    {
        // Ảnh KHÔNG cần xử lý ở đây — form sửa gắn thẳng qua FilePond context header
        // (X-Context-Type=banner, X-Context-Id=$banner->id), xem spec §8.
        $banner->update([
            'placement'        => $data->placement,
            'target_type'      => $data->target_type,
            'target_value'     => $data->target_value,
            'title'            => $data->title,
            'alt_text'         => $data->alt_text,
            'link_url'         => $data->link_url,
            'open_in_new_tab'  => $data->open_in_new_tab,
            'badge_label'      => $data->badge_label,
            'start_date'       => $data->start_date,
            'end_date'         => $data->end_date,
            'sort_order'       => $data->sort_order,
            'is_active'        => $data->is_active,
            'updated_by'       => auth()->id(),
        ]);

        return $banner;
    }
}
