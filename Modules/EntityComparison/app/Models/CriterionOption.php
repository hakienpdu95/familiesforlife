<?php

namespace Modules\EntityComparison\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.4/§0 mục 8 — option cho Criterion type
 * select|multi_select. Không SoftDeletes/LogsActivity — con của Criterion, lifecycle theo cha
 * (đúng convention SurveyFieldOption/PostComparisonColumn).
 */
class CriterionOption extends Model
{
    protected $table = 'criterion_options';

    protected $fillable = ['criterion_id', 'value', 'label', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
