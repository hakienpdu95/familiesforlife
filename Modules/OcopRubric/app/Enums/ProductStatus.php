<?php

namespace Modules\OcopRubric\Enums;

enum ProductStatus: string
{
    case Draft        = 'draft';
    case Practicing    = 'practicing';
    case SelfAssessed  = 'self_assessed';
    case Archived      = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Nháp',
            self::Practicing  => 'Đang luyện tập',
            self::SelfAssessed => 'Đã tự đánh giá',
            self::Archived    => 'Đã lưu trữ',
        };
    }
}
