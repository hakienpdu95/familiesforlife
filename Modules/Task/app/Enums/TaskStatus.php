<?php

namespace Modules\Task\Enums;

enum TaskStatus: string
{
    case Backlog    = 'backlog';
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case InReview   = 'in_review';
    case Done       = 'done';
    case Cancelled  = 'cancelled';
    case Blocked    = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Backlog    => 'Chờ xử lý',
            self::Todo       => 'Cần làm',
            self::InProgress => 'Đang làm',
            self::InReview   => 'Đang review',
            self::Done       => 'Hoàn thành',
            self::Cancelled  => 'Đã hủy',
            self::Blocked    => 'Bị chặn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Backlog    => 'badge-ghost',
            self::Todo       => 'badge-neutral',
            self::InProgress => 'badge-info',
            self::InReview   => 'badge-warning',
            self::Done       => 'badge-success',
            self::Cancelled  => 'badge-ghost',
            self::Blocked    => 'badge-error',
        };
    }

    public function isDone(): bool
    {
        return $this === self::Done;
    }

    public function countsAsDenominator(): bool
    {
        return $this !== self::Cancelled;
    }
}
