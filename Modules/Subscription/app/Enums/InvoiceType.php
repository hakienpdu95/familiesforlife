<?php

namespace Modules\Subscription\Enums;

enum InvoiceType: string
{
    case New     = 'new';     // first subscription
    case Renewal = 'renewal'; // periodic renewal
    case Upgrade = 'upgrade'; // plan upgrade payment
}
