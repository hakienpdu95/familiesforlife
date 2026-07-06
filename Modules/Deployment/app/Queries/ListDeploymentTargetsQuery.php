<?php

namespace Modules\Deployment\Queries;

class ListDeploymentTargetsQuery
{
    public function __construct(
        public readonly string  $vertical_code,
        public readonly ?string $phase   = null,
        public readonly ?string $search  = null,
        public readonly ?int    $project_id = null,
    ) {}
}
