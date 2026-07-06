<?php

namespace App\Foundation\Vertical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerticalPhase extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vertical_template_id', 'key', 'label', 'sort_order',
        'is_initial', 'auto_assign_data_collection',
    ];

    protected $casts = [
        'is_initial'                  => 'boolean',
        'auto_assign_data_collection' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(VerticalTemplate::class, 'vertical_template_id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(VerticalChecklistItem::class)->orderBy('sort_order');
    }
}
