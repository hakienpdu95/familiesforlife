<?php

namespace Modules\Post\Enums;

enum ArticleStatus: string
{
    case Draft         = 'draft';
    case PendingReview = 'pending_review';
    case Published     = 'published';
    case Scheduled     = 'scheduled';
    case Archived      = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft         => 'Nháp',
            self::PendingReview => 'Chờ duyệt',
            self::Published     => 'Đã xuất bản',
            self::Scheduled     => 'Đã lên lịch',
            self::Archived      => 'Đã lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft         => 'badge-ghost',
            self::PendingReview => 'badge-warning',
            self::Published     => 'badge-success',
            self::Scheduled     => 'badge-info',
            self::Archived      => 'badge-neutral',
        };
    }
}
