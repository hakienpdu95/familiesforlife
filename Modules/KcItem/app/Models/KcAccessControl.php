<?php

namespace Modules\KcItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcAccessControl extends Model
{
    protected $table = 'kc_access_controls';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'item_id',
        'target_type',
        'target_id',
        'permission',
        'granted_at',
        'granted_by',
        'expired_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(KcItem::class, 'item_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'granted_by');
    }
}
