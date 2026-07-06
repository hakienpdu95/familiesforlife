<?php

namespace App\Foundation\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

/**
 * Base model for all tenant-scoped domain entities.
 *
 * Provides: multi-tenancy scoping, soft deletes, and activity logging.
 * All domain models in Modules should extend this.
 */
abstract class TenantAwareModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToOrganization;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
