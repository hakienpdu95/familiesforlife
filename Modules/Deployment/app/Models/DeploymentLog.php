<?php

namespace Modules\Deployment\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Audit trail thuần, append-only — giống DeploymentProgressLog, không cần timestamps/soft-delete. */
class DeploymentLog extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'deployment_id', 'step', 'message', 'level', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->created_at ??= now();
        });
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
