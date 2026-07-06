<?php

namespace Modules\JobPosting\Enums;

enum SalaryType: string
{
    case Monthly = 'monthly';
    case Yearly  = 'yearly';
    case Hourly  = 'hourly';
    case Project = 'project';

    public function label(): string
    {
        return match($this) {
            self::Monthly => 'Lương tháng',
            self::Yearly  => 'Lương năm',
            self::Hourly  => 'Lương giờ',
            self::Project => 'Theo dự án',
        };
    }
}
