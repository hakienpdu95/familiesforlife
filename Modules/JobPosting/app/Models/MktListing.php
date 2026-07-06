<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;

class MktListing extends Model
{
    protected $table = 'mkt_listings';

    protected $fillable = [
        'uuid',
        'jp_job_post_id',
        'org_id',
        'poster_type',
        'listing_type',
        'title',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'salary_currency',
        'employment_type',
        'work_type',
        'experience_level',
        'location',
        'headcount',
        'status',
        'expire_at',
        'closed_at',
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'expire_at'  => 'datetime',
        'closed_at'  => 'datetime',
    ];
}
