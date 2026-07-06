<?php

namespace Modules\Marketplace\Enums;

enum EmploymentType: string
{
    case FULL_TIME   = 'full_time';
    case PART_TIME   = 'part_time';
    case CONTRACTOR  = 'contractor';
    case FREELANCE   = 'freelance';
    case INTERN      = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::FULL_TIME   => 'Toàn thời gian',
            self::PART_TIME   => 'Bán thời gian',
            self::CONTRACTOR  => 'Hợp đồng',
            self::FREELANCE   => 'Freelance',
            self::INTERN      => 'Thực tập',
        };
    }
}
