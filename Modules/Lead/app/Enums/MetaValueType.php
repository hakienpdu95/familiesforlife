<?php

namespace Modules\Lead\Enums;

enum MetaValueType: int
{
    case String   = 1;
    case Integer  = 2;
    case Decimal  = 3;
    case Boolean  = 4;
    case Datetime = 5;
}
