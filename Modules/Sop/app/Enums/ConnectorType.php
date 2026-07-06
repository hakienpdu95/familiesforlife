<?php

namespace Modules\Sop\Enums;

enum ConnectorType: string
{
    case Sequence  = 'sequence';
    case YesBranch = 'yes_branch';
    case NoBranch  = 'no_branch';
    case Trigger   = 'trigger';
    case Return_   = 'return';
    case Exception = 'exception';

    public function label(): string
    {
        return match($this) {
            self::Sequence  => 'Tuần tự',
            self::YesBranch => 'Nhánh Có',
            self::NoBranch  => 'Nhánh Không',
            self::Trigger   => 'Kích hoạt',
            self::Return_   => 'Quay về',
            self::Exception => 'Ngoại lệ',
        };
    }

    public function defaultColor(): string
    {
        return match($this) {
            self::Sequence  => '#B4B2A9',
            self::YesBranch => '#639922',
            self::NoBranch  => '#E24B4A',
            self::Trigger   => '#7F77DD',
            self::Return_   => '#EF9F27',
            self::Exception => '#E24B4A',
        };
    }

    public function dashed(): bool
    {
        return match($this) {
            self::Trigger, self::Exception => true,
            default                        => false,
        };
    }
}
