<?php

namespace Modules\Video\Features\PublicReading\Http;

use Illuminate\View\View;
use Modules\Video\Models\Video;

class VideoPublicController
{
    public function index(): View
    {
        $videos = Video::active()->orderBy('sort_order')->paginate(config('video.per_page', 12));

        return view('video::public.index', compact('videos'));
    }
}
