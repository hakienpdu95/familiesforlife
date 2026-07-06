<?php

namespace Modules\Assessment\Events;

use Modules\Assessment\Models\SandboxSession;

class SandboxCompleted
{
    public function __construct(
        public readonly SandboxSession $session,
    ) {}
}
