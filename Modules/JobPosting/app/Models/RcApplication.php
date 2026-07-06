<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RcApplication extends Model
{
    protected $table = 'rc_applications';

    protected $fillable = [
        'uuid',
        'jp_job_post_id',
        'candidate_id',
        'org_id',
        'apply_source',
        'mkt_application_id',
        'status',
        'cover_letter',
        'answers',
        'disqualified',
    ];

    protected $casts = [
        'answers'      => 'array',
        'disqualified' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RcCandidate::class, 'candidate_id');
    }
}
