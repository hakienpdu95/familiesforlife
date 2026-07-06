<?php

namespace Modules\Lead\Events;

class TagDeleted
{
    public function __construct(
        public readonly int $tagId,
        public readonly int $organizationId,
    ) {}
}
