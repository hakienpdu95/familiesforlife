<?php

namespace Modules\WorkflowAutomation\Models;

use App\Foundation\Models\TenantAwareModel;
use Modules\WorkflowAutomation\Enums\ConditionMatch;
use Modules\WorkflowAutomation\Enums\CooldownType;
use Modules\WorkflowAutomation\Enums\WorkflowStatus;

class Workflow extends TenantAwareModel
{
    protected $fillable = [
        'organization_id', 'name', 'description',
        'category', 'icon', 'color', 'tags',
        'definition_status', 'version',
        'trigger_type',
        'condition_match', 'cooldown_type',
        'cooldown_window_min', 'cooldown_count_max',
        'allowed_trigger_roles', 'template_id',
        'is_active', 'priority',
        'run_count', 'last_run_at', 'last_run_status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'condition_match'       => 'integer',
        'cooldown_type'         => 'integer',
        'definition_status'     => 'integer',
        'version'               => 'integer',
        'last_run_status'       => 'integer',
        'last_run_at'           => 'datetime',
        'tags'                  => 'array',
        'allowed_trigger_roles' => 'array',
    ];

    public function conditions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowCondition::class)->orderBy('sort_order');
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sort_order');
    }

    public function stepGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowStepGroup::class)->orderBy('sort_order');
    }

    public function variables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowVariable::class);
    }

    public function inputFields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowInputField::class)->orderBy('sort_order');
    }

    public function executions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowExecution::class)->latest('triggered_at');
    }

    public function triggerParams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowTriggerParam::class);
    }

    public function template(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function parsedParams(): array
    {
        return $this->triggerParams->mapWithKeys(function (WorkflowTriggerParam $p) {
            return [$p->param_key => $p->castValue()];
        })->all();
    }

    public function variablesMap(): array
    {
        return $this->variables->mapWithKeys(fn ($v) => [$v->var_key => $v->var_value])->all();
    }

    public function getConditionMatchEnumAttribute(): ConditionMatch
    {
        return ConditionMatch::from($this->condition_match ?? 3);
    }

    public function getCooldownTypeEnumAttribute(): CooldownType
    {
        return CooldownType::from($this->cooldown_type ?? 0);
    }

    public function getLastRunStatusEnumAttribute(): ?WorkflowStatus
    {
        return $this->last_run_status ? WorkflowStatus::from($this->last_run_status) : null;
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
