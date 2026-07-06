<?php

namespace Modules\LeadSource\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    protected $table = 'lead_sources';

    protected $fillable = [
        'organization_id', 'is_global', 'code', 'label', 'icon',
        'color', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_global'  => 'boolean',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(\Modules\Lead\Models\Lead::class, 'source_id');
    }
}
