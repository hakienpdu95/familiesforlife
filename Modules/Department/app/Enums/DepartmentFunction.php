<?php

namespace Modules\Department\Enums;

enum DepartmentFunction: string
{
    case General         = 'general';
    case Sales           = 'sales';
    case Marketing       = 'marketing';
    case Finance         = 'finance';
    case Hr              = 'hr';
    case It              = 'it';
    case Operations      = 'operations';
    case CustomerService = 'customer_service';
    case Legal           = 'legal';
    case Rd              = 'rd';
    case Other           = 'other';

    public function label(): string
    {
        return match ($this) {
            self::General         => 'Chung',
            self::Sales           => 'Kinh doanh',
            self::Marketing       => 'Marketing',
            self::Finance         => 'Tài chính - Kế toán',
            self::Hr              => 'Nhân sự',
            self::It              => 'Công nghệ thông tin',
            self::Operations      => 'Vận hành',
            self::CustomerService => 'Chăm sóc khách hàng',
            self::Legal           => 'Pháp chế',
            self::Rd              => 'Nghiên cứu & Phát triển',
            self::Other           => 'Khác',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::General         => 'badge-ghost',
            self::Sales           => 'badge-info',
            self::Marketing       => 'badge-secondary',
            self::Finance         => 'badge-warning',
            self::Hr              => 'badge-primary',
            self::It              => 'badge-accent',
            self::Operations      => 'badge-neutral',
            self::CustomerService => 'badge-success',
            self::Legal           => 'badge-ghost',
            self::Rd              => 'badge-info',
            self::Other           => 'badge-ghost',
        };
    }
}
