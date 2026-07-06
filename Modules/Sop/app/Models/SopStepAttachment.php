<?php

namespace Modules\Sop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopStepAttachment extends Model
{
    public $timestamps = false;

    protected $table = 'sop_step_attachments';

    protected $fillable = [
        'uuid',
        'step_id',
        'file_name',
        'file_url',
        'file_type',
        'file_size_kb',
        'storage_provider',
        'storage_key',
        'alt_text',
        'sort_order',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function step(): BelongsTo
    {
        return $this->belongsTo(SopStep::class, 'step_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getFileSizeLabelAttribute(): string
    {
        $kb = $this->file_size_kb;
        if ($kb >= 1024) {
            return round($kb / 1024, 1) . ' MB';
        }
        return $kb . ' KB';
    }
}
