<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JpJobPostStat extends Model
{
    public $timestamps = false;

    protected $table = 'jp_job_post_stats';

    protected $fillable = [
        'uuid',
        'job_post_id',
        'stat_date',
        'source',
        'view_count',
        'unique_view_count',
        'apply_count',
        'share_count',
        'bookmark_count',
    ];

    protected $casts = [
        'stat_date' => 'date',
    ];

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JpJobPost::class, 'job_post_id');
    }
}
