<?php

namespace App\Foundation\Vertical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerticalConfigItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vertical_template_id', 'config_group',
        'code', 'label', 'is_required', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(VerticalTemplate::class, 'vertical_template_id');
    }
}
