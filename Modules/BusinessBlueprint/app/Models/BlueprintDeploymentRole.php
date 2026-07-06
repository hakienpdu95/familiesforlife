<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Thay thế `blueprint_deployment_settings.setting_key='default_roles'` (JSON) —
 * xem migration 2026_07_06_000011.
 */
class BlueprintDeploymentRole extends Model
{
    protected $table = 'blueprint_deployment_roles';

    protected $fillable = [
        'blueprint_version_id', 'role_code', 'role_name', 'description', 'sort_order',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }
}
