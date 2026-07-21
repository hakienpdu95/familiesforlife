<?php

namespace Modules\Page\Features\PublicReading\Http;

use Illuminate\View\View;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class PagePublicController
{
    /**
     * spec/Page_Static_Pages_Technical_Specification.md §5 — view chọn động qua
     * $page->resolveView(), không hard-code page::public.show. view_count chỉ mang tính
     * tham khảo (§3.4), không chống bot.
     */
    public function __invoke(Page $page): View
    {
        abort_unless($page->status === PageStatus::Published, 404);

        $page->increment('view_count');

        return view($page->resolveView(), ['page' => $page]);
    }
}
