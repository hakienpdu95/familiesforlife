<?php

namespace Modules\KcItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcVersionHistory extends Model
{
    protected $table = 'kc_version_histories';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'item_id',
        'version_number',
        'title_snapshot',
        'content_snapshot',
        'change_summary',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(KcItem::class, 'item_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
