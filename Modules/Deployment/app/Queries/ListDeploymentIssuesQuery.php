<?php

namespace Modules\Deployment\Queries;

class ListDeploymentIssuesQuery
{
    public function __construct(
        public readonly ?int    $target_id = null,
        public readonly ?int    $project_id = null,
        public readonly ?string $severity  = null,
        public readonly ?string $status    = null,
        public readonly ?string $issue_type = null,
    ) {}
}
