<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SandboxActivity extends Model
{
    protected $table = 'sandbox_activities';

    protected $fillable = [
        'sandbox_session_id',
        'activity_type',
        'activity_description',
        'ai_tool_used',
        'quality_note',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at'  => 'datetime',
            'quality_note' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SandboxSession::class, 'sandbox_session_id');
    }
}
