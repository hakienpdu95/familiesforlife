<?php

namespace Modules\ContentBrief\Enums;

/** spec/ContentBrief_Technical_Specification.md §6.0. */
enum GenerationStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Đang chờ',
            self::Processing => 'Đang xử lý',
            self::Completed  => 'Hoàn tất',
            self::Failed     => 'Thất bại',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending    => 'badge-ghost',
            self::Processing => 'badge-warning',
            self::Completed  => 'badge-success',
            self::Failed     => 'badge-error',
        };
    }
}
