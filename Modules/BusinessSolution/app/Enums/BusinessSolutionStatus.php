<?php

namespace Modules\BusinessSolution\Enums;

enum BusinessSolutionStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Published => 'Đã phát hành',
            self::Archived  => 'Đã lưu trữ',
        };
    }
}
