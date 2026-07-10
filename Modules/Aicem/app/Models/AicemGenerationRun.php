<?php

namespace Modules\Aicem\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Aicem\Enums\GenerationRunStatus;

class AicemGenerationRun extends TenantAwareModel
{
    protected $table = 'aicem_generation_runs';

    protected $fillable = [
        // organization_id: bình thường trait BelongsToOrganization tự điền theo TenantContext
        // ambient, nhưng StartGenerationRunAction gán TƯỜNG MINH từ $subject->organization_id
        // (đúng với super-admin thao tác cross-tenant — xem docblock StartGenerationRunAction) —
        // cần khai fillable để mass-assignment không bị chặn.
        'organization_id',
        'subject_type',
        'subject_id',
        'workflow_id',
        'requested_by',
        'provider',
        'model',
        'status',
        'input_tokens',
        'output_tokens',
        'cache_creation_tokens',
        'cache_read_tokens',
        'estimated_cost_usd',
        'cost_usd',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'              => GenerationRunStatus::class,
            'input_tokens'        => 'integer',
            'output_tokens'       => 'integer',
            'cache_creation_tokens' => 'integer',
            'cache_read_tokens'     => 'integer',
            'estimated_cost_usd'  => 'float',
            'cost_usd'            => 'float',
            'started_at'          => 'datetime',
            'completed_at'        => 'datetime',
        ];
    }

    /** Model của subject (PostArticle|Product) tra qua config('aicem_subjects') — không dùng morphTo() (mục 7). */
    public function subject(): ?\Illuminate\Database\Eloquent\Model
    {
        $modelClass = config("aicem_subjects.{$this->subject_type}.model");

        return $modelClass ? $modelClass::withoutGlobalScopes()->find($this->subject_id) : null;
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AicemWorkflow::class, 'workflow_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AicemSuggestion::class, 'generation_run_id');
    }
}
