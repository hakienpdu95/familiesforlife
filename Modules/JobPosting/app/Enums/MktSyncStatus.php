<?php

namespace Modules\JobPosting\Enums;

enum MktSyncStatus: string
{
    case Synced     = 'synced';
    case OutOfSync  = 'out_of_sync';

    public function label(): string
    {
        return match($this) {
            self::Synced    => 'Đã đồng bộ',
            self::OutOfSync => 'Chưa đồng bộ',
        };
    }
}
