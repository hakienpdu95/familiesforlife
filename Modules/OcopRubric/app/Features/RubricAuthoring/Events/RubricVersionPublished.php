<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\OcopRubric\Models\OcopRubricVersion;

class RubricVersionPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly OcopRubricVersion $version) {}
}
