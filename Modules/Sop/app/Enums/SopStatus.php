<?php

namespace Modules\Sop\Enums;

enum SopStatus: string
{
    case Draft         = 'draft';
    case PendingReview = 'pending_review';
    case Approved      = 'approved';
    case Rejected      = 'rejected';
    case Archived      = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Draft         => 'Nháp',
            self::PendingReview => 'Chờ duyệt',
            self::Approved      => 'Đã duyệt',
            self::Rejected      => 'Bị từ chối',
            self::Archived      => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft         => 'badge-ghost',
            self::PendingReview => 'badge-warning',
            self::Approved      => 'badge-success',
            self::Rejected      => 'badge-error',
            self::Archived      => 'badge-neutral',
        };
    }
}
