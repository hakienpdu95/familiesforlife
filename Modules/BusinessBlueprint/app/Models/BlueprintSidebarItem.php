<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Thay thế `blueprint_deployment_settings.setting_key='sidebar_config'` (JSON) —
 * xem migration 2026_07_06_000012.
 */
class BlueprintSidebarItem extends Model
{
    protected $table = 'blueprint_sidebar_items';

    protected $fillable = [
        'blueprint_version_id', 'parent_id', 'module_key', 'label', 'icon', 'sort_order',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
