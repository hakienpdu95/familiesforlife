<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Persona extends Model
{
    use LogsActivity;
    protected $table = 'personas';

    protected $fillable = [
        'assessment_code',
        'persona_code',
        'label',
        'description',
        'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('scoring');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PersonaCondition::class, 'persona_id')->orderBy('sort_order');
    }

    public function scopeForAssessment(Builder $query, string $code): Builder
    {
        return $query->where('assessment_code', $code)->orderBy('sort_order');
    }
}
