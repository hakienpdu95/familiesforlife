<?php

namespace Modules\Newsletter\Enums;

enum SubscriberStatus: string
{
    case PendingConfirmation = 'pending_confirmation'; // §0 mục 14 — chỉ dùng khi NEWSLETTER_DOUBLE_OPT_IN=true
    case Active              = 'active';
    case Unsubscribed        = 'unsubscribed';
    case Bounced             = 'bounced';    // đồng bộ từ webhook EmailBounced, xem §9.3
    case Complained          = 'complained'; // đồng bộ từ webhook EmailComplained

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Chờ xác nhận email',
            self::Active               => 'Đang nhận tin',
            self::Unsubscribed         => 'Đã huỷ đăng ký',
            self::Bounced              => 'Email không gửi được (bounce)',
            self::Complained           => 'Đã báo cáo spam',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'badge-info',
            self::Active               => 'badge-success',
            self::Unsubscribed         => 'badge-ghost',
            self::Bounced              => 'badge-warning',
            self::Complained           => 'badge-error',
        };
    }
}
