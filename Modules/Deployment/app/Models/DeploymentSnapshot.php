<?php

namespace Modules\Deployment\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/** Audit trail thuần, append-only. Xem migration cho lý do không có snapshot_data JSON. */
class DeploymentSnapshot extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'deployment_id', 'snapshot_type', 'blueprint_version_id', 'created_at',
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

    public function blueprintVersion(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class);
    }

    public function configItems(): HasMany
    {
        return $this->hasMany(DeploymentConfigSnapshotItem::class);
    }
}
