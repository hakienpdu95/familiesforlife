<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportImpactHighlight extends Model
{
    protected $table = 'passport_impact_highlights';

    public $timestamps = false;

    protected $fillable = [
        'passport_entry_id',
        'source_impact_id',
        'title',
        'impact_category',
        'impact_type',
        'baseline_value',
        'achieved_value',
        'improvement_pct',
        'roi_pct',
        'period_label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'baseline_value'   => 'float',
            'achieved_value'   => 'float',
            'improvement_pct'  => 'float',
            'roi_pct'          => 'float',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PassportEntry::class, 'passport_entry_id');
    }
}
