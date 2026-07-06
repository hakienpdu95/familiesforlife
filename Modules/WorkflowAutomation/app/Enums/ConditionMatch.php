<?php
namespace Modules\WorkflowAutomation\Enums;

enum ConditionMatch: int
{
    case All  = 1;
    case Any  = 2;
    case None = 3;
}
