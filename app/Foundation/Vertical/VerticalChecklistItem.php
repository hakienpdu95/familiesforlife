<?php

namespace App\Foundation\Vertical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerticalChecklistItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vertical_phase_id', 'key', 'label', 'is_required', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(VerticalPhase::class, 'vertical_phase_id');
    }
}
