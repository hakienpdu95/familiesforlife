<?php

namespace Modules\N8n\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * spec/N8n_Integration_Technical_Specification.md §2.4 — vì module không còn dựa vào lịch sử
 * chạy của module khác. Bảng audit — không phải thực thể nghiệp vụ (cùng lý do §2.3).
 */
class N8nOutboundLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'n8n_outbound_logs';

    protected $fillable = [
        'connection_id',
        'event_name',
        'caller',
        'success',
        'http_status',
        'duration_ms',
        'error_message',
        'payload_excerpt',
        'requested_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'requested_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        // §2.5 — withTrashed(), cùng lý do N8nInboundLog::connection().
        return $this->belongsTo(N8nConnection::class, 'connection_id')->withTrashed();
    }
}
