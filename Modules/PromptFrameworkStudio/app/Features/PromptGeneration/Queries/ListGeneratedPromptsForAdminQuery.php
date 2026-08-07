<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Queries;

use App\Shared\Contracts\QueryInterface;

class ListGeneratedPromptsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $frameworkKey = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'updated_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
