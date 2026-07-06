<?php

namespace Modules\PerformanceReview\Enums;

enum ReviewStatus: string
{
    case Draft        = 'draft';
    case Submitted    = 'submitted';
    case Acknowledged = 'acknowledged';
    case Finalized    = 'finalized';
    case Cancelled    = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft        => 'Nháp',
            self::Submitted    => 'Đã nộp',
            self::Acknowledged => 'Đã xác nhận',
            self::Finalized    => 'Hoàn tất',
            self::Cancelled    => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft        => 'badge-ghost',
            self::Submitted    => 'badge-info',
            self::Acknowledged => 'badge-warning',
            self::Finalized    => 'badge-success',
            self::Cancelled    => 'badge-error',
        };
    }
}
