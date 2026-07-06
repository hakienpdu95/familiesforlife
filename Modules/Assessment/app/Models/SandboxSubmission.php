<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SandboxSubmission extends Model
{
    protected $table = 'sandbox_submissions';

    protected $fillable = [
        'sandbox_session_id',
        'submitted_content',
        'ai_tools_used',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function usedAiTools(): array
    {
        return $this->ai_tools_used
            ? array_filter(array_map('trim', explode('|', $this->ai_tools_used)))
            : [];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SandboxSession::class, 'sandbox_session_id');
    }
}
