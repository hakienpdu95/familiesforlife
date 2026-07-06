<?php

namespace Modules\Task\Enums;

enum TaskPriority: string
{
    case Critical = 'critical';
    case High     = 'high';
    case Medium   = 'medium';
    case Low      = 'low';
    case None     = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Khẩn cấp',
            self::High     => 'Cao',
            self::Medium   => 'Trung bình',
            self::Low      => 'Thấp',
            self::None     => 'Không có',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Critical => 'badge-error',
            self::High     => 'badge-warning',
            self::Medium   => 'badge-info',
            self::Low      => 'badge-ghost',
            self::None     => 'badge-ghost',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Critical => 1,
            self::High     => 2,
            self::Medium   => 3,
            self::Low      => 4,
            self::None     => 5,
        };
    }
}
