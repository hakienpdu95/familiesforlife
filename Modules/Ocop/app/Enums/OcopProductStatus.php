<?php

namespace Modules\Ocop\Enums;

/**
 * spec/Province_Showcase_Technical_Specification.md §5 — workflow Post-style (draft/published),
 * KHÔNG có bước chờ duyệt/approve/reject như Event: chỉ đội biên tập nội bộ (permission
 * ocop.manage) mới tạo được sản phẩm, không có nguồn nộp public nào cần kiểm soát thêm.
 */
enum OcopProductStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Published => 'Đã xuất bản',
        };
    }
}
