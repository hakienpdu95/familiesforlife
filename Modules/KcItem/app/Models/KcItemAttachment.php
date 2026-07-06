<?php

namespace Modules\KcItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcItemAttachment extends Model
{
    protected $table = 'kc_item_attachments';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'item_id',
        'file_name',
        'file_url',
        'file_type',
        'file_size_kb',
        'storage_provider',
        'storage_key',
        'sort_order',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(KcItem::class, 'item_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
