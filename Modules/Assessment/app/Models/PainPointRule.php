<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class PainPointRule extends Model
{
    use LogsActivity;
    protected $table = 'pain_point_rules';

    protected $fillable = [
        'assessment_code',
        'pain_point_code',
        'label',
        'required_flags',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('scoring');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeForAssessment(Builder $query, string $assessmentCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode)->where('is_active', true);
    }
}
