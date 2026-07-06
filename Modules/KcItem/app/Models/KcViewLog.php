<?php

namespace Modules\KcItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcViewLog extends Model
{
    protected $table = 'kc_view_logs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'item_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(KcItem::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
