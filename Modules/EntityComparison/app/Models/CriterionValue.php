<?php

namespace Modules\EntityComparison\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.5/§5 — giá trị thực tế của 1 Criterion cho 1
 * Entity. Không SoftDeletes/LogsActivity — dữ liệu con, lifecycle theo Entity/Criterion cha (đúng
 * convention PostComparisonRow: khối con của 1 aggregate không tự log riêng). KHÔNG đọc/ghi cột
 * value_* trực tiếp ở đây hay ở nơi khác — luôn đi qua CriterionValueResolver (§3.5.2 mục 3).
 */
class CriterionValue extends Model
{
    protected $table = 'criterion_values';

    protected $fillable = [
        'entity_id', 'criterion_id',
        'value_string', 'value_number', 'value_number_max', 'value_bool', 'value_date',
        'option_id', 'value_json',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_number_max' => 'decimal:4',
        'value_bool' => 'boolean',
        'value_date' => 'date',
        'value_json' => 'array',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }

    /** select (đơn). */
    public function option(): BelongsTo
    {
        return $this->belongsTo(CriterionOption::class, 'option_id');
    }

    /**
     * multi_select — xem §3.5.1. Khoá pivot phải khai tường minh: mặc định Eloquent suy ra
     * relatedPivotKey từ tên model (`criterion_option_id`), KHÔNG khớp cột thật `option_id` của
     * bảng criterion_value_options (§3.5) — thiếu dòng này sẽ lỗi "column not found" khi sync().
     */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(CriterionOption::class, 'criterion_value_options', 'criterion_value_id', 'option_id');
    }
}
