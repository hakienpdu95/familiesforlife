<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class JpJobPostBenefit extends Model
{
    public $timestamps = false;

    protected $table = 'jp_job_post_benefits';

    protected $fillable = [
        'uuid',
        'job_post_id',
        'benefit_id',
        'benefit_name',
        'description',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JpJobPost::class, 'job_post_id');
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(JpBenefitMaster::class, 'benefit_id');
    }
}
