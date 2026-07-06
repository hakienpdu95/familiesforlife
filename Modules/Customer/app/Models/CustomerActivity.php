<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Enums\CustomerActivityType;

class CustomerActivity extends Model
{
    protected $table = 'customer_activities';

    public $timestamps = false;

    protected $fillable = [
        'customer_id', 'lead_id', 'organization_id',
        'type', 'title', 'description', 'outcome',
        'scheduled_at', 'completed_at', 'duration_minutes',
        'actor_id', 'actor_name', 'created_at',
    ];

    protected $casts = [
        'type'         => CustomerActivityType::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
