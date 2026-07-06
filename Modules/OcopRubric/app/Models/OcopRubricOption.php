<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 phương án chọn của tiêu chí lá — system-level, không extend TenantAwareModel.
 */
class OcopRubricOption extends Model
{
    protected $table = 'ocop_rubric_options';

    protected $fillable = [
        'criterion_id', 'label', 'points', 'sort_order',
    ];

    protected $casts = [
        'points' => 'decimal:2',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(OcopRubricCriterion::class, 'criterion_id');
    }
}
