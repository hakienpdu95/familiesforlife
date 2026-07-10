<?php

namespace Modules\Aicem\Enums;

enum ExampleCandidateStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
