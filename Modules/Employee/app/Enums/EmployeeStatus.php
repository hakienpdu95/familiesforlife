<?php

namespace Modules\Employee\Enums;

enum EmployeeStatus: string
{
    case Active     = 'active';
    case Probation  = 'probation';
    case OnLeave    = 'on_leave';
    case Resigned   = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active     => 'Đang làm việc',
            self::Probation  => 'Thử việc',
            self::OnLeave    => 'Nghỉ phép',
            self::Resigned   => 'Đã nghỉ việc',
            self::Terminated => 'Đã chấm dứt',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active     => 'badge-success',
            self::Probation  => 'badge-info',
            self::OnLeave    => 'badge-warning',
            self::Resigned   => 'badge-ghost',
            self::Terminated => 'badge-error',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Probation, self::OnLeave]);
    }
}
