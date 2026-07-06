<?php

namespace Modules\AiCopilot\Models;

use App\Models\User;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiRequest extends Model
{
    use BelongsToOrganization;

    protected $table    = 'ai_requests';
    public $timestamps  = true;

    protected $fillable = [
        'uuid', 'organization_id', 'user_id', 'agent_id', 'prompt_id',
        'subject_type', 'subject_id', 'rendered_prompt', 'input_variables',
        'ai_output', 'finish_reason', 'provider', 'model',
        'input_tokens', 'output_tokens', 'total_tokens', 'cost_usd', 'duration_ms',
        'status', 'error_message', 'queued_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'input_variables' => 'array',
        'queued_at'       => 'datetime',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'cost_usd'        => 'decimal:6',
    ];

    // Guard mutability — immutable after completion
    public function save(array $options = []): bool
    {
        if ($this->exists && in_array($this->getOriginal('status'), ['done', 'failed'])) {
            throw new \RuntimeException('AiRequest is immutable after completion.');
        }
        return parent::save($options);
    }

    public function agent(): BelongsTo  { return $this->belongsTo(AiAgent::class); }
    public function prompt(): BelongsTo { return $this->belongsTo(AiPrompt::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function subject(): MorphTo  { return $this->morphTo(); }

    public function scopeCurrentMonth(Builder $q): Builder
    {
        return $q->where('created_at', '>=', now()->startOfMonth());
    }

    public function scopeDone(Builder $q): Builder { return $q->where('status', 'done'); }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isDone(): bool     { return $this->status === 'done'; }
    public function isFailed(): bool   { return $this->status === 'failed'; }

    public function costFormatted(): string
    {
        return '$' . number_format((float) $this->cost_usd, 4);
    }
}
