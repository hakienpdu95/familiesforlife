<?php

namespace Modules\JobPosting\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobPosting\Models\JpJobPost;

class DestroyJpJobPostAction
{
    use AsAction;

    public function handle(JpJobPost $post): string
    {
        $title = $post->title;
        $post->delete();
        return $title;
    }
}
