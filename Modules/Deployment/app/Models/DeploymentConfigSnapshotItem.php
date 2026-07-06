<?php

namespace Modules\Deployment\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 dòng config (theo configurable_type + configurable_id) đang hiệu lực tại thời
 * điểm deploy — thay thế snapshot_data JSON cho snapshot_type='organization_config'.
 */
class DeploymentConfigSnapshotItem extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'deployment_snapshot_id', 'configurable_type', 'configurable_id', 'created_at',
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

    public function deploymentSnapshot(): BelongsTo
    {
        return $this->belongsTo(DeploymentSnapshot::class);
    }
}
