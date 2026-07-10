<?php

namespace Modules\Aicem\Enums;

enum GenerationRunStatus: string
{
    case Pending   = 'pending';
    case Running   = 'running';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';
}
