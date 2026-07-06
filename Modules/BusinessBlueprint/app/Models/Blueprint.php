<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\BusinessSolution\Models\BusinessSolution;

/**
 * System-level (không extend TenantAwareModel) — Blueprint là dữ liệu Definition
 * cấp hệ thống, dùng chung/thư viện, không thuộc về 1 tổ chức (đúng A09.2 §5.1).
 */
class Blueprint extends Model
{
    use SoftDeletes;

    protected $table = 'blueprints';

    protected $fillable = [
        'business_solution_id', 'code', 'name', 'description',
        'current_version_id', 'status', 'created_by',
    ];

    public function businessSolution(): BelongsTo
    {
        return $this->belongsTo(BusinessSolution::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BlueprintVersion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
