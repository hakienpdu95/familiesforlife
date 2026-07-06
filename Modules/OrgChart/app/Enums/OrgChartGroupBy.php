<?php

namespace Modules\OrgChart\Enums;

enum OrgChartGroupBy: string
{
    case Department = 'department';
    case Branch     = 'branch';
    case JobTitle   = 'job_title';
    case Manager    = 'manager';

    public function label(): string
    {
        return match($this) {
            self::Department => 'Phòng ban',
            self::Branch     => 'Chi nhánh',
            self::JobTitle   => 'Chức danh',
            self::Manager    => 'Quản lý',
        };
    }
}
