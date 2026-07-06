<?php

namespace Modules\Assessment\Enums;

enum CampaignStatus: string
{
    case Draft    = 'draft';
    case Open     = 'open';
    case Closed   = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Nháp',
            self::Open     => 'Đang mở',
            self::Closed   => 'Đã đóng',
            self::Archived => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft    => 'badge-ghost',
            self::Open     => 'badge-success',
            self::Closed   => 'badge-warning',
            self::Archived => 'badge-neutral',
        };
    }

    public function isJoinable(): bool
    {
        return $this === self::Open;
    }
}
