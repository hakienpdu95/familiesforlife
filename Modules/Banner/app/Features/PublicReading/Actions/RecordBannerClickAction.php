<?php

namespace Modules\Banner\Features\PublicReading\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Banner\Models\Banner;

/**
 * spec/Banner_Management_Technical_Specification.md §5.4 — cùng pattern
 * RecordProductBlockClickAction (Modules/Post).
 */
class RecordBannerClickAction
{
    use AsAction;

    public function handle(Banner $banner): ?string
    {
        if (! $banner->link_url) {
            return null;
        }

        $banner->increment('click_count');

        return $banner->link_url;
    }
}
