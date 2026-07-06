<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaCondition extends Model
{
    protected $table = 'persona_conditions';

    protected $fillable = [
        'persona_id',
        'target_type',
        'target_code',
        'operator',
        'threshold_value',
        'flag_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'float',
            'flag_value'      => 'boolean',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
