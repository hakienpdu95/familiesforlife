<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SandboxTask extends Model
{
    protected $table = 'sandbox_tasks';

    protected $fillable = [
        'uuid',
        'sandbox_env_id',
        'target_position_code',
        'title',
        'instruction',
        'expected_output',
        'scoring_rubric',
        'time_limit_minutes',
        'ai_tools_allowed',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'           => 'boolean',
            'time_limit_minutes'  => 'integer',
            'sort_order'          => 'integer',
        ];
    }

    public function scoringRubricItems(): array
    {
        return $this->scoring_rubric
            ? array_filter(array_map('trim', explode('|', $this->scoring_rubric)))
            : [];
    }

    public function allowedAiTools(): array
    {
        return $this->ai_tools_allowed
            ? array_filter(array_map('trim', explode('|', $this->ai_tools_allowed)))
            : [];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(SandboxEnvironment::class, 'sandbox_env_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SandboxSession::class, 'sandbox_task_id');
    }
}
