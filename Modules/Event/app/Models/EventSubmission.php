<?php

namespace Modules\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PII người nộp sự kiện — tách biệt hoàn toàn khỏi Event (spec/Event_Management_Technical_
 * Specification.md §5.3). NGUYÊN TẮC BẮT BUỘC: không route/query công khai nào được
 * `with('submission')` hay serialize model này ra JSON công khai — chỉ dùng nội bộ ở
 * Features/EventModeration (dashboard, §10.5).
 */
class EventSubmission extends Model
{
    protected $table = 'event_submissions';

    protected $fillable = [
        'event_id',
        'submitter_first_name',
        'submitter_last_name',
        'submitter_email',
        'newsletter_consent',
        'consented_at',
        'source',
        'ip_address',
        'user_agent',
        'turnstile_verified',
    ];

    protected $casts = [
        'newsletter_consent'  => 'boolean',
        'consented_at'        => 'datetime',
        'turnstile_verified'  => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
