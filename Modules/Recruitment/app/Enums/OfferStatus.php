<?php

namespace Modules\Recruitment\Enums;

enum OfferStatus: string
{
    case Draft           = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved        = 'approved';
    case Sent            = 'sent';
    case Accepted        = 'accepted';
    case Rejected        = 'rejected';
    case Expired         = 'expired';
    case Revoked         = 'revoked';

    public function label(): string
    {
        return match($this) {
            self::Draft           => 'Bản nháp',
            self::PendingApproval => 'Chờ duyệt',
            self::Approved        => 'Đã duyệt',
            self::Sent            => 'Đã gửi',
            self::Accepted        => 'Đã chấp nhận',
            self::Rejected        => 'Đã từ chối',
            self::Expired         => 'Hết hạn',
            self::Revoked         => 'Đã thu hồi',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft           => 'badge-ghost',
            self::PendingApproval => 'badge-warning',
            self::Approved        => 'badge-info',
            self::Sent            => 'badge-primary',
            self::Accepted        => 'badge-success',
            self::Rejected        => 'badge-error',
            self::Expired         => 'badge-ghost',
            self::Revoked         => 'badge-error',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected, self::Expired, self::Revoked]);
    }
}
