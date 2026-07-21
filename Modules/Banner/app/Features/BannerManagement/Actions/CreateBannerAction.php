<?php

namespace Modules\Banner\Features\BannerManagement\Actions;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Banner\Features\BannerManagement\Data\BannerData;
use Modules\Banner\Models\Banner;

class CreateBannerAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(BannerData $data): Banner
    {
        $banner = Banner::create([
            'placement'         => $data->placement,
            'target_type'       => $data->target_type,
            'target_value'      => $data->target_value,
            'title'             => $data->title,
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

        // spec/Media_Library_Technical_Specification.md §8 — form tạo mới chưa có banner.id lúc
        // FilePond upload, ảnh tạm gắn ở FilePondDraft — "nhận" vào banner thật vừa tạo.
        if ($data->cover_media_uuid) {
            $this->mediaUpload->reassociateFilePondDrafts($banner, [$data->cover_media_uuid], 'banner');
        }

        return $banner;
    }
}
