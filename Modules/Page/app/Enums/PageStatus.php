<?php

namespace Modules\Page\Enums;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §0/§3.1 — không có pending/scheduled
 * như TranslationStatus của Post: trang tĩnh không qua editorial workflow, xuất bản trực tiếp.
 */
enum PageStatus: string
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
