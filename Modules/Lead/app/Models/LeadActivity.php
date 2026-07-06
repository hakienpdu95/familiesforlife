<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Lead\Enums\LeadActivityType;

class LeadActivity extends Model
{
    protected $table = 'lead_activities';

    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'organization_id', 'type', 'title', 'description',
        'outcome', 'scheduled_at', 'completed_at',
        'duration_minutes', 'attendee_count',
        'actor_id', 'actor_name', 'created_at',
    ];

    protected $casts = [
        'type'         => LeadActivityType::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
