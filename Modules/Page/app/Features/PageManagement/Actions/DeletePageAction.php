<?php

namespace Modules\Page\Features\PageManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Page\Models\Page;

class DeletePageAction
{
    use AsAction;

    /**
     * spec/Page_Static_Pages_Technical_Specification.md §3.3 — is_system chặn CẢ soft-delete
     * lẫn hard-delete. Trang hệ thống chỉ "ẩn" được qua status=draft hoặc seo_noindex, không
     * có khái niệm xoá-mềm-rồi-khôi-phục riêng cho is_system.
     */
    public function handle(Page $page): void
    {
        throw_if($page->is_system, ValidationException::withMessages([
            'page' => 'Không thể xoá trang hệ thống — hãy chuyển về "Nháp" hoặc bật "Không lập chỉ mục" để ẩn thay vì xoá.',
        ]));

        $page->delete();
    }
}
