<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStageHistory extends Model
{
    protected $table = 'lead_stage_history';

    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'organization_id',
        'stage_from_id', 'stage_to_id',
        'stage_from_label', 'stage_to_label',
        'changed_by', 'changed_by_name',
        'note', 'changed_at', 'created_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
