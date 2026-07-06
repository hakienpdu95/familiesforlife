<?php

namespace Modules\Lead\Events;

use Modules\Lead\Models\LeadTagDefinition;

class TagUpdated
{
    public function __construct(public readonly LeadTagDefinition $tag) {}
}
