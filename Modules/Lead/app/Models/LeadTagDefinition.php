<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;

class LeadTagDefinition extends Model
{
    protected $table = 'lead_tag_definitions';

    protected $fillable = ['organization_id', 'name', 'color'];

    public function leads(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_tag_map', 'tag_id', 'lead_id');
    }
}
