<?php

namespace Modules\KcItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcFeedback extends Model
{
    protected $table = 'kc_feedbacks';

    protected $fillable = [
        'uuid',
        'item_id',
        'user_id',
        'rating',
        'comment',
        'is_helpful',
    ];

    protected $casts = [
        'rating'     => 'integer',
        'is_helpful' => 'boolean',
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
