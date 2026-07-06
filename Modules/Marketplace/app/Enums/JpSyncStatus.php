<?php

namespace Modules\Marketplace\Enums;

enum JpSyncStatus: string
{
    case SYNCED      = 'synced';
    case OUT_OF_SYNC = 'out_of_sync';

    public function label(): string
    {
        return match ($this) {
            self::SYNCED      => 'Đồng bộ',
            self::OUT_OF_SYNC => 'Lỗi thời',
        };
    }
}
