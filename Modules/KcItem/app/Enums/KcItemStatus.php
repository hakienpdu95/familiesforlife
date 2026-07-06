<?php

namespace Modules\KcItem\Enums;

enum KcItemStatus: string
{
    case Draft         = 'draft';
    case PendingReview = 'pending_review';
    case Approved      = 'approved';
    case Rejected      = 'rejected';
    case Archived      = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft         => 'Bản nháp',
            self::PendingReview => 'Chờ duyệt',
            self::Approved      => 'Đã duyệt',
            self::Rejected      => 'Bị từ chối',
            self::Archived      => 'Lưu trữ',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft         => 'ghost',
            self::PendingReview => 'warning',
            self::Approved      => 'success',
            self::Rejected      => 'error',
            self::Archived      => 'ghost',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'text' => $case->label()],
            self::cases()
        );
    }
}
