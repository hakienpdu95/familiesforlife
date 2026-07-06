<?php

namespace Modules\Subscription\Enums;

enum TransactionStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Failed    = 'failed';
    case Duplicate = 'duplicate'; // webhook received but invoice already paid
}
