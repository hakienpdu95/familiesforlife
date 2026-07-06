<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RcCandidate extends Model
{
    protected $table = 'rc_candidates';

    protected $fillable = [
        'uuid',
        'org_id',
        'full_name',
        'email',
        'phone',
        'resume_url',
        'portfolio_url',
        'linkedin_url',
        'source',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(RcApplication::class, 'candidate_id');
    }
}
