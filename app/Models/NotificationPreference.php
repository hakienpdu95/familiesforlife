<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'event_type',
        'channel_db',
        'channel_mail',
        'channel_push',
    ];

    protected $casts = [
        'channel_db'   => 'boolean',
        'channel_mail' => 'boolean',
        'channel_push' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
