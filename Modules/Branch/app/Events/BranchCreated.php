<?php

namespace Modules\Branch\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Branch\Models\Branch;

class BranchCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Branch $branch,
    ) {}
}
