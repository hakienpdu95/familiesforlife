<?php

namespace Modules\JobPosting\Enums;

enum Industry: string
{
    case Technology   = 'technology';
    case Finance      = 'finance';
    case Healthcare   = 'healthcare';
    case Education    = 'education';
    case Retail       = 'retail';
    case Manufacturing= 'manufacturing';
    case Marketing    = 'marketing';
    case HR           = 'hr';
    case Legal        = 'legal';
    case Construction = 'construction';
    case Hospitality  = 'hospitality';
    case Logistics    = 'logistics';
    case Other        = 'other';

    public function label(): string
    {
        return match($this) {
            self::Technology    => 'Công nghệ',
            self::Finance       => 'Tài chính',
            self::Healthcare    => 'Y tế',
            self::Education     => 'Giáo dục',
            self::Retail        => 'Bán lẻ',
            self::Manufacturing => 'Sản xuất',
            self::Marketing     => 'Marketing',
            self::HR            => 'Nhân sự',
            self::Legal         => 'Pháp lý',
            self::Construction  => 'Xây dựng',
            self::Hospitality   => 'Khách sạn / Du lịch',
            self::Logistics     => 'Logistics',
            self::Other         => 'Khác',
        };
    }
}
