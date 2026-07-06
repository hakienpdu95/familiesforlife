<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MktApplicantExperience extends Model
{
    protected $table = 'mkt_applicant_experiences';

    protected $fillable = [
        'uuid', 'applicant_id', 'company_name', 'title', 'description',
        'start_month', 'start_year', 'end_month', 'end_year', 'is_current', 'sort_order',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MktApplicant::class, 'applicant_id');
    }
}
