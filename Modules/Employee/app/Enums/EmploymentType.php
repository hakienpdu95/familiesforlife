<?php

namespace Modules\Employee\Enums;

enum EmploymentType: string
{
    case FullTime   = 'full_time';
    case PartTime   = 'part_time';
    case Contractor = 'contractor';
    case Probation  = 'probation';
    case Intern     = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::FullTime   => 'Toàn thời gian',
            self::PartTime   => 'Bán thời gian',
            self::Contractor => 'Hợp đồng',
            self::Probation  => 'Thử việc',
            self::Intern     => 'Thực tập',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::FullTime   => 'badge-primary',
            self::PartTime   => 'badge-info',
            self::Contractor => 'badge-warning',
            self::Probation  => 'badge-secondary',
            self::Intern     => 'badge-ghost',
        };
    }
}
