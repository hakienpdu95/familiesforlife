<?php

namespace Modules\JobTitle\Enums;

enum JobTitleCategory: string
{
    case Executive   = 'executive';
    case Manager     = 'manager';
    case Supervisor  = 'supervisor';
    case Staff       = 'staff';
    case Intern      = 'intern';
    case Consultant  = 'consultant';

    public function label(): string
    {
        return match ($this) {
            self::Executive  => 'Ban lãnh đạo',
            self::Manager    => 'Quản lý',
            self::Supervisor => 'Giám sát',
            self::Staff      => 'Nhân viên',
            self::Intern     => 'Thực tập sinh',
            self::Consultant => 'Tư vấn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Executive  => 'badge-error',
            self::Manager    => 'badge-warning',
            self::Supervisor => 'badge-info',
            self::Staff      => 'badge-success',
            self::Intern     => 'badge-ghost',
            self::Consultant => 'badge-secondary',
        };
    }
}
