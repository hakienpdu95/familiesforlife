<?php

namespace Modules\Organization\Enums;

enum ExitReason: string
{
    case Resigned          = 'resigned';
    case Terminated        = 'terminated';
    case Retired           = 'retired';
    case ContractEnd       = 'contract_end';
    case InternalTransfer  = 'internal_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Resigned         => 'Tự nguyện nghỉ',
            self::Terminated       => 'Bị chấm dứt hợp đồng',
            self::Retired          => 'Nghỉ hưu',
            self::ContractEnd      => 'Hết hợp đồng',
            self::InternalTransfer => 'Chuyển nội bộ',
        };
    }
}
