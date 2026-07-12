<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Models\User;
use App\Shared\Contracts\QueryInterface;

class ListPendingReviewTranslationsQuery implements QueryInterface
{
    public function __construct(
        public readonly User $user,
    ) {}
}
