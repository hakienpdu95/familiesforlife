<?php

namespace Modules\LeadSource\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\LeadSource\Models\LeadSource;

class SourceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LeadSource $source,
    ) {}
}
