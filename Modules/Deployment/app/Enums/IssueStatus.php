<?php

namespace Modules\Deployment\Enums;

enum IssueStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open       => 'Mở',
            self::InProgress => 'Đang xử lý',
            self::Resolved   => 'Đã giải quyết',
            self::Closed     => 'Đã đóng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Open       => 'badge-error',
            self::InProgress => 'badge-warning',
            self::Resolved   => 'badge-success',
            self::Closed     => 'badge-neutral',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::InProgress]);
    }
}
