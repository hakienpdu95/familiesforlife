<?php

namespace Modules\Employee\Enums;

enum EmployeeHistoryChangeType: string
{
    case Hire            = 'hire';
    case BranchTransfer  = 'branch_transfer';
    case DeptTransfer    = 'dept_transfer';
    case Promotion       = 'promotion';
    case Demotion        = 'demotion';
    case ManagerChange   = 'manager_change';
    case SalaryChange    = 'salary_change';
    case Leave           = 'leave';
    case ReturnFromLeave = 'return_from_leave';
    case Resign          = 'resign';
    case Terminate       = 'terminate';
    case Separation      = 'separation';

    public function label(): string
    {
        return match ($this) {
            self::Hire            => 'Tuyển dụng',
            self::BranchTransfer  => 'Chuyển chi nhánh',
            self::DeptTransfer    => 'Chuyển phòng ban',
            self::Promotion       => 'Thăng chức',
            self::Demotion        => 'Giáng chức',
            self::ManagerChange   => 'Thay đổi quản lý',
            self::SalaryChange    => 'Điều chỉnh lương',
            self::Leave           => 'Nghỉ phép',
            self::ReturnFromLeave => 'Trở lại sau nghỉ phép',
            self::Resign          => 'Từ chức',
            self::Terminate       => 'Chấm dứt hợp đồng',
            self::Separation      => 'Thôi việc',
        };
    }
}
