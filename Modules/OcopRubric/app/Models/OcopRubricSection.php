<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phần A/B/C của 1 bộ tiêu chí — system-level, không extend TenantAwareModel.
 */
class OcopRubricSection extends Model
{
    protected $table = 'ocop_rubric_sections';

    protected $fillable = [
        'rubric_version_id', 'code', 'label', 'max_score', 'sort_order',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
    ];

    public function rubricVersion(): BelongsTo
    {
        return $this->belongsTo(OcopRubricVersion::class, 'rubric_version_id');
    }

    /** Toàn bộ node (Mục + tiêu chí lá) của phần này — dùng children()/options() để duyệt cây. */
    public function criteria(): HasMany
    {
        return $this->hasMany(OcopRubricCriterion::class, 'rubric_section_id')->orderBy('sort_order');
    }
}
