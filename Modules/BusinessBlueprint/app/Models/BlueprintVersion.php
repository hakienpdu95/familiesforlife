<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;

class BlueprintVersion extends Model
{
    use SoftDeletes;

    protected $table = 'blueprint_versions';

    protected $fillable = [
        'blueprint_id', 'version', 'status', 'release_note',
        'published_at', 'published_by', 'parent_version_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(BlueprintOutcome::class)->orderBy('sort_order');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(BlueprintCapability::class)->orderBy('sort_order');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(BlueprintWorkflow::class)->orderBy('sort_order');
    }

    public function resourceLinks(): HasMany
    {
        return $this->hasMany(BlueprintResourceLink::class)->orderBy('sort_order');
    }

    public function aiCapabilities(): HasMany
    {
        return $this->hasMany(BlueprintAiCapability::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(BlueprintAnalytic::class);
    }

    public function deploymentRoles(): HasMany
    {
        return $this->hasMany(BlueprintDeploymentRole::class)->orderBy('sort_order');
    }

    public function sidebarItems(): HasMany
    {
        return $this->hasMany(BlueprintSidebarItem::class)->orderBy('sort_order');
    }

    public function statusEnum(): BlueprintVersionStatus
    {
        return BlueprintVersionStatus::from($this->status);
    }

    public function isImmutable(): bool
    {
        return $this->statusEnum()->isImmutable();
    }
}
