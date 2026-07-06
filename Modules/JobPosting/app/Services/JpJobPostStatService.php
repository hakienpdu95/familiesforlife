<?php

namespace Modules\JobPosting\Services;

use Illuminate\Support\Str;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostStat;

class JpJobPostStatService
{
    public function recordView(JpJobPost $post, string $source = 'direct'): void
    {
        $stat = JpJobPostStat::firstOrCreate(
            [
                'job_post_id' => $post->id,
                'stat_date'   => today()->toDateString(),
                'source'      => $source,
            ],
            ['uuid' => Str::uuid()->toString()]
        );

        JpJobPostStat::where('id', $stat->id)->increment('view_count');
        JpJobPost::where('id', $post->id)->increment('view_count');
    }

    public function recordApply(JpJobPost $post, string $source = 'direct'): void
    {
        $stat = JpJobPostStat::firstOrCreate(
            [
                'job_post_id' => $post->id,
                'stat_date'   => today()->toDateString(),
                'source'      => $source,
            ],
            ['uuid' => Str::uuid()->toString()]
        );

        JpJobPostStat::where('id', $stat->id)->increment('apply_count');
    }
}
