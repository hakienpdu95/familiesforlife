<?php

namespace Modules\Post\Features\ArticleAuthoring\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Post\Models\PostArticleTranslation;

class ArticlePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PostArticleTranslation $translation) {}
}
