<?php

namespace Modules\Aicem\Enums;

enum GenerationRunStatus: string
{
    case Pending   = 'pending';
    case Running   = 'running';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => __('Đang chờ'),
            self::Running   => __('Đang chạy'),
            self::Succeeded => __('Thành công'),
            self::Failed    => __('Thất bại'),
        };
    }
}
