<?php

namespace Modules\Subscription\Enums;

enum GatewayType: string
{
    case Redirect = 'redirect'; // VNPay — builds URL, user leaves site
    case Monitor  = 'monitor';  // SePay — show bank transfer instructions, wait for webhook
    case Manual   = 'manual';   // Admin/dev — no external call, instant confirm
}
