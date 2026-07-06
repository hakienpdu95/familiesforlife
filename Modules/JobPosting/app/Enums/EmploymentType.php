<?php

namespace Modules\JobPosting\Enums;

enum EmploymentType: string
{
    case FullTime   = 'full_time';
    case PartTime   = 'part_time';
    case Contract   = 'contract';
    case Freelance  = 'freelance';
    case Internship = 'internship';
    case Temporary  = 'temporary';

    public function label(): string
    {
        return match($this) {
            self::FullTime   => 'Toàn thời gian',
            self::PartTime   => 'Bán thời gian',
            self::Contract   => 'Hợp đồng',
            self::Freelance  => 'Tự do',
            self::Internship => 'Thực tập',
            self::Temporary  => 'Tạm thời',
        };
    }
}
