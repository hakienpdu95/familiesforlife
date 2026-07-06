<?php

namespace Modules\BusinessSolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ngành dọc (vertical) — system-level, dùng chung mọi tổ chức, không extend TenantAwareModel.
 */
class Vertical extends Model
{
    use SoftDeletes;

    protected $table = 'verticals';

    protected $fillable = [
        'code', 'name', 'description', 'icon', 'status', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function businessSolutions(): HasMany
    {
        return $this->hasMany(BusinessSolution::class);
    }
}
