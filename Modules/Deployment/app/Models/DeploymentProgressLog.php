<?php

namespace Modules\Deployment\Models;

use App\Models\User;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentProgressLog extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'deployment_target_id',
        'deployment_checklist_item_id',
        'phase',
        'percent',
        'remark',
        'logged_by',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'percent'   => 'integer',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(DeploymentTarget::class, 'deployment_target_id');
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(DeploymentChecklistItem::class, 'deployment_checklist_item_id');
    }
}
