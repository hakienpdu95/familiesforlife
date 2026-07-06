<?php

namespace Modules\ActivityLog\Enums;

enum ActorType: int
{
    case User     = 1;
    case System   = 2;
    case ApiToken = 3;
    case Job      = 4;
    case Guest    = 5;
}
