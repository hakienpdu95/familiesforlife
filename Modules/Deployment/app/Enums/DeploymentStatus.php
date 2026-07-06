<?php

namespace Modules\Deployment\Enums;

enum DeploymentStatus: string
{
    case Pending    = 'pending';
    case Running    = 'running';
    case Completed  = 'completed';
    case Failed     = 'failed';
    case RolledBack = 'rolled_back';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Chờ xử lý',
            self::Running    => 'Đang chạy',
            self::Completed  => 'Hoàn tất',
            self::Failed     => 'Thất bại',
            self::RolledBack => 'Đã rollback',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending    => 'badge-ghost',
            self::Running    => 'badge-info',
            self::Completed  => 'badge-success',
            self::Failed     => 'badge-error',
            self::RolledBack => 'badge-warning',
        };
    }
}
