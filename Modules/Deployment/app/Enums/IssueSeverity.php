<?php

namespace Modules\Deployment\Enums;

enum IssueSeverity: string
{
    case Critical = 'critical';
    case High     = 'high';
    case Medium   = 'medium';
    case Low      = 'low';

    public function label(): string
    {
        return match($this) {
            self::Critical => 'Nghiêm trọng',
            self::High     => 'Cao',
            self::Medium   => 'Trung bình',
            self::Low      => 'Thấp',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Critical => 'badge-error',
            self::High     => 'badge-warning',
            self::Medium   => 'badge-info',
            self::Low      => 'badge-success',
        };
    }
}
