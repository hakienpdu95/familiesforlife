<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * spec/Newsletter_Technical_Specification.md §4.2/§6 — append-only, không sửa, không soft-delete.
 * Không FK sang NewsletterSubscriber (Broadcast gửi tới cả Segment, không gắn 1 subscriber).
 */
class NewsletterBroadcastLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'newsletter_broadcast_logs';

    protected $fillable = ['resend_broadcast_id', 'subject', 'scheduled_at', 'sent_by'];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by');
    }
}
