<?php

namespace Modules\OrganizationSolution\Enums;

enum OrganizationSolutionStatus: string
{
    case Draft       = 'draft';
    case Configuring = 'configuring';
    case Ready       = 'ready';
    case Deploying   = 'deploying';
    case Running     = 'running';
    case Suspended   = 'suspended';
    case Archived    = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Nháp',
            self::Configuring => 'Đang cấu hình',
            self::Ready       => 'Sẵn sàng',
            self::Deploying   => 'Đang triển khai',
            self::Running     => 'Đang vận hành',
            self::Suspended   => 'Tạm ngưng',
            self::Archived    => 'Đã lưu trữ',
        };
    }
}
