<?php

namespace Modules\LeadPipelineStage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadPipelineStage extends Model
{
    protected $table = 'lead_pipeline_stages';

    protected $fillable = [
        'organization_id', 'is_global', 'code', 'label', 'color',
        'sort_order', 'is_won', 'is_lost', 'probability', 'is_active',
    ];

    protected $casts = [
        'is_global'  => 'boolean',
        'is_won'     => 'boolean',
        'is_lost'    => 'boolean',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'probability' => 'integer',
    ];

    public function isTerminal(): bool
    {
        return $this->is_won || $this->is_lost;
    }

    public function leads(): HasMany
    {
        return $this->hasMany(\Modules\Lead\Models\Lead::class, 'stage_id');
    }
}
