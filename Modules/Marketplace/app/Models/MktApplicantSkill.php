<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Marketplace\Enums\ProficiencyLevel;

class MktApplicantSkill extends Model
{
    protected $table = 'mkt_applicant_skills';
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'applicant_id', 'skill_name', 'proficiency_level', 'years_used', 'sort_order',
    ];

    protected $casts = [
        'proficiency_level' => ProficiencyLevel::class,
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
