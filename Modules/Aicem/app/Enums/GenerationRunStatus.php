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
            self::Pending   => 'Đang chờ',
            self::Running   => 'Đang chạy',
            self::Succeeded => 'Thành công',
            self::Failed    => 'Thất bại',
        };
    }
}
