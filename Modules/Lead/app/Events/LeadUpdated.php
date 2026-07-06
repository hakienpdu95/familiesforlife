<?php

namespace Modules\Lead\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Lead\Models\Lead;

class LeadUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Lead $lead) {}
}
