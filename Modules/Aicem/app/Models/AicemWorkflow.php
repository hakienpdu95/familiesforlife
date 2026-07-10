<?php

namespace Modules\Aicem\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AicemWorkflow extends TenantAwareModel
{
    protected $table = 'aicem_workflows';

    protected $fillable = [
        'subject_type',
        'slug',
        'name',
        'prompt_template',
        'filters',
        'context_template_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'filters'   => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function contextTemplate(): BelongsTo
    {
        return $this->belongsTo(AicemContextTemplate::class, 'context_template_id');
    }

    public function generationRuns(): HasMany
    {
        return $this->hasMany(AicemGenerationRun::class, 'workflow_id');
    }
}
