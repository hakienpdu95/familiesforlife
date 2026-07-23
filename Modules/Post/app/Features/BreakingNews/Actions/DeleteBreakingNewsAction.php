<?php

namespace Modules\Post\Features\BreakingNews\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostBreakingNews;

class DeleteBreakingNewsAction
{
    use AsAction;

    public function handle(PostBreakingNews $breakingNews): void
    {
        $breakingNews->delete();
    }
}
