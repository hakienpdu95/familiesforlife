<?php

namespace Modules\Leave\Enums;

enum LeaveRequestStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Chờ duyệt',
            self::Approved  => 'Đã duyệt',
            self::Rejected  => 'Từ chối',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending   => 'badge-warning',
            self::Approved  => 'badge-success',
            self::Rejected  => 'badge-error',
            self::Cancelled => 'badge-ghost',
        };
    }
}
