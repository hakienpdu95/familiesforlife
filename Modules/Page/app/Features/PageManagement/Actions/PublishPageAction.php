<?php

namespace Modules\Page\Features\PageManagement\Actions;

use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class PublishPageAction
{
    use AsAction;

    /**
     * spec/Page_Static_Pages_Technical_Specification.md §3.3 — 2 chốt chặn kỹ thuật trước khi
     * cho publish, không dựa vào kỷ luật vận hành:
     *   1. content bắt buộc khi template = 'default' (trang thường không có nội dung để hiện).
     *   2. View::exists($page->resolveView()) — chặn publish sớm khi dev CHƯA tạo view cho
     *      template riêng (vd seeder tạo trang "about" ở Phase 2 nhưng view chỉ có ở Phase 5a).
     */
    public function handle(Page $page): Page
    {
        throw_if($page->template === 'default' && blank($page->content), ValidationException::withMessages([
            'content' => 'Cần nhập nội dung trước khi xuất bản.',
        ]));

        throw_unless(View::exists($page->resolveView()), ValidationException::withMessages([
            'template' => 'Chưa thể xuất bản: giao diện cho template này chưa sẵn sàng, liên hệ đội kỹ thuật.',
        ]));

        $page->update([
            'status'       => PageStatus::Published,
            'published_at' => $page->published_at ?? now(),
        ]);

        return $page;
    }
}
