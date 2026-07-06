<?php

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\ActivityLog\Enums\HttpMethod;

class ActivityLogHttp extends Model
{
    public $timestamps = false;

    protected $table = 'activity_log_http';

    protected $fillable = [
        'log_id', 'http_method', 'url', 'route_name',
        'status_code', 'duration_ms', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'http_method' => HttpMethod::class,
        'created_at'  => 'datetime',
    ];

    public function log(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ActivityLog::class, 'log_id');
    }

    public function getHttpMethodLabelAttribute(): string
    {
        return $this->http_method?->name ?? '';
    }
}
