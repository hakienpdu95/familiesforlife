<?php

namespace Modules\Page\Features\PageManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Page\Features\PageManagement\Data\PageData;
use Modules\Page\Models\Page;

class UpdatePageAction
{
    use AsAction;

    /**
     * spec/Page_Static_Pages_Technical_Specification.md §3.3 — đổi slug/template được phép
     * (cảnh báo ở UI, §4.2), Action chỉ ghi lại giá trị đã validate. Slug rỗng ở update giữ
     * nguyên slug hiện tại (form luôn hiển thị sẵn giá trị — rỗng chỉ xảy ra khi cố tình xoá).
     */
    public function handle(Page $page, PageData $data): Page
    {
        $page->update([
            'slug'             => $data->slug !== null && $data->slug !== '' ? $data->slug : $page->slug,
            'title'            => $data->title,
            'template'         => $data->template,
            'content'          => $data->content,
            'excerpt'          => $data->excerpt,
            'seo_title'        => $data->seo_title,
            'seo_description'  => $data->seo_description,
            'seo_noindex'      => $data->seo_noindex,
            'sort_order'       => $data->sort_order,
            'updated_by'       => auth()->id(),
        ]);

        return $page;
    }
}
