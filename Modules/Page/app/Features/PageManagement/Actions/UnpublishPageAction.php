<?php

namespace Modules\Page\Features\PageManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class UnpublishPageAction
{
    use AsAction;

    /** published_at KHÔNG bị xoá — giữ mốc lần xuất bản đầu tiên (spec §3.3). */
    public function handle(Page $page): Page
    {
        $page->update(['status' => PageStatus::Draft]);

        return $page;
    }
}
