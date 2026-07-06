<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nút trong cây tiêu chí (Mục hoặc Tiêu chí lá), tự tham chiếu qua parent_id +
 * materialized path (path/depth) — cùng pattern Branch. System-level, không
 * extend TenantAwareModel. path/depth được UpsertCriterionAction (Phase 2)
 * tính lại mỗi khi tạo/di chuyển node, không tự tính trong model.
 */
class OcopRubricCriterion extends Model
{
    protected $table = 'ocop_rubric_criteria';

    protected $fillable = [
        'rubric_section_id', 'parent_id', 'path', 'depth', 'code', 'label',
        'max_score', 'requirement_note', 'is_scorable', 'sort_order',
    ];

    protected $casts = [
        'max_score'   => 'decimal:2',
        'is_scorable' => 'boolean',
        'depth'       => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(OcopRubricSection::class, 'rubric_section_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** Eager-load đệ quy toàn bộ cây con (bất kể độ sâu) — dùng cho GetRubricTreeHandler/ValidateRubricIntegrityHandler. */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive', 'options');
    }

    public function options(): HasMany
    {
        return $this->hasMany(OcopRubricOption::class, 'criterion_id')->orderBy('sort_order');
    }
}
