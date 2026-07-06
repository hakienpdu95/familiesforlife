<?php

namespace Modules\Sop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sop\Models\SopProcess;

class SopProcessUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly SopProcess $sop) {}
}
