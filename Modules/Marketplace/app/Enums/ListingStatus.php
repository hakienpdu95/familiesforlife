<?php

namespace Modules\Marketplace\Enums;

enum ListingStatus: string
{
    case DRAFT          = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case ACTIVE         = 'active';
    case PAUSED         = 'paused';
    case CLOSED         = 'closed';
    case EXPIRED        = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT          => 'Nháp',
            self::PENDING_REVIEW => 'Chờ duyệt',
            self::ACTIVE         => 'Đang hiển thị',
            self::PAUSED         => 'Tạm dừng',
            self::CLOSED         => 'Đã đóng',
            self::EXPIRED        => 'Hết hạn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE         => 'badge-success',
            self::PENDING_REVIEW => 'badge-warning',
            self::DRAFT          => 'badge-ghost',
            self::PAUSED         => 'badge-info',
            self::CLOSED,
            self::EXPIRED        => 'badge-error',
        };
    }
}
