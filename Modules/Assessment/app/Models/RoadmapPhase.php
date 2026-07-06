<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoadmapPhase extends Model
{
    protected $table = 'roadmap_phases';

    protected $fillable = [
        'assessment_code',
        'maturity_level',
        'band_code',
        'phase_code',
        'title',
        'description',
        'duration_weeks',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_weeks' => 'integer',
        ];
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(RoadmapMilestone::class, 'phase_id')->orderBy('sort_order');
    }

    public function scopeForMaturityLevel(Builder $query, string $assessmentCode, string $maturityLevel): Builder
    {
        return $query->where('assessment_code', $assessmentCode)
            ->where('maturity_level', $maturityLevel)
            ->orderBy('sort_order');
    }

    public function scopeForBand(Builder $query, string $assessmentCode, string $bandCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode)
            ->where(function ($q) use ($bandCode) {
                $q->where('band_code', $bandCode)
                    ->orWhere(function ($q2) use ($bandCode) {
                        $q2->whereNull('band_code')->where('maturity_level', $bandCode);
                    });
            })
            ->orderBy('sort_order');
    }
}
