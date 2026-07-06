<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforceProfileHistory extends Model
{
    protected $table = 'workforce_profile_histories';

    protected $fillable = [
        'workforce_profile_id',
        'event_type',
        'source_id',
        'source_type',
        'tdwcf_score_before',
        'tdwcf_score_after',
        'maturity_level_before',
        'maturity_level_after',
        'change_delta',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at'         => 'datetime',
            'tdwcf_score_before'  => 'float',
            'tdwcf_score_after'   => 'float',
            'change_delta'        => 'float',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkforceProfile::class, 'workforce_profile_id');
    }
}
