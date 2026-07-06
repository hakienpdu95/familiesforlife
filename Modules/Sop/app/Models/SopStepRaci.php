<?php

namespace Modules\Sop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopStepRaci extends Model
{
    public $timestamps = false;

    protected $table = 'sop_step_raci';

    protected $fillable = [
        'uuid',
        'step_id',
        'assignee_type',
        'assignee_id',
        'raci_type',
        'notes',
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function assigneeName(): string
    {
        if ($this->assignee_type === 'user') {
            $user = \App\Models\User::find($this->assignee_id);
            return $user?->name ?? "User #{$this->assignee_id}";
        }

        $role = \Spatie\Permission\Models\Role::find($this->assignee_id);
        return $role?->name ?? "Role #{$this->assignee_id}";
    }
}
