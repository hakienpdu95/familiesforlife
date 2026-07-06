<?php

namespace Modules\Marketplace\Enums;

enum ApplicationImportStatus: string
{
    case NotImported = 'not_imported';
    case Imported    = 'imported';
    case Skipped     = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::NotImported => 'Chưa import',
            self::Imported    => 'Đã import',
            self::Skipped     => 'Bỏ qua',
        };
    }
}
