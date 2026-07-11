<?php

namespace Modules\Post\Features\PublicReading\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticleTranslation;

class IncrementArticleViewCountAction
{
    use AsAction;

    public function handle(PostArticleTranslation $translation): void
    {
        $translation->increment('view_count');
    }
}
