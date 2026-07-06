<?php

namespace Modules\Subscription\Enums;

enum ChangeType: string
{
    case Subscribe  = 'subscribe';
    case Upgrade    = 'upgrade';
    case Downgrade  = 'downgrade';
    case Cancel     = 'cancel';
    case Resume     = 'resume';
    case Renew      = 'renew';
}
