<?php

namespace Modules\Lead\Enums;

enum LeadSourceCode: string
{
    case Manual   = 'manual';
    case Survey   = 'survey';
    case Import   = 'import';
    case Api      = 'api';
    case Workflow = 'workflow';
    case Referral = 'referral';
    case Event    = 'event';
    case Website  = 'website';
}
