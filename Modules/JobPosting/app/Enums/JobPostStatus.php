<?php

namespace Modules\JobPosting\Enums;

enum JobPostStatus: string
{
    case Draft          = 'draft';
    case PendingReview  = 'pending_review';
    case Published      = 'published';
    case Paused         = 'paused';
    case Closed         = 'closed';
    case Archived       = 'archived';
    case Cancelled      = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft         => 'Nháp',
            self::PendingReview => 'Chờ duyệt',
            self::Published     => 'Đang tuyển',
            self::Paused        => 'Tạm dừng',
            self::Closed        => 'Đã đóng',
            self::Archived      => 'Lưu trữ',
            self::Cancelled     => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft         => 'badge-ghost',
            self::PendingReview => 'badge-warning',
            self::Published     => 'badge-success',
            self::Paused        => 'badge-info',
            self::Closed        => 'badge-error',
            self::Archived      => 'badge-neutral',
            self::Cancelled     => 'badge-error',
        };
    }
}
