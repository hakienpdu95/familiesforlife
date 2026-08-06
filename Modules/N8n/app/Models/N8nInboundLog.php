<?php

namespace Modules\N8n\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * spec/N8n_Integration_Technical_Specification.md §2.3 — audit MỌI lệnh gọi vào, kể cả không
 * khớp gì. Bảng audit tần suất cao, mục đích debug ngắn hạn rồi tự dọn (§5.7) — KHÔNG
 * SoftDeletes, KHÔNG LogsActivity, KHÔNG organization_id.
 */
class N8nInboundLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'n8n_inbound_logs';

    protected $fillable = [
        'connection_id',
        'ip_address',
        'signature_valid',
        'http_status_returned',
        'event_name',
        'listener_count',
        'payload_excerpt',
        'error_message',
        'received_at',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'http_status_returned' => 'integer',
        'listener_count' => 'integer',
        'received_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        // §2.5 — withTrashed(): log lịch sử KHÔNG mất liên kết khi connection bị xoá mềm, người
        // xem log vẫn cần thấy tên/kết nối đã xoá để tra lịch sử.
        return $this->belongsTo(N8nConnection::class, 'connection_id')->withTrashed();
    }
}
