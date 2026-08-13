<?php

namespace Modules\EntityComparison\Enums;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §4 — string-backed (đúng convention phổ biến
 * nhất trong repo, VD OcopProductStatus) thay vì int-backed như Survey\FieldType. Không cần enum
 * ValueKind tách riêng như Survey vì mỗi case ở đây đã map 1-1 với 1 cách lưu trong
 * criterion_values (§3.5) — không có 2 case nào chia sẻ chung 1 kind lưu trữ.
 */
enum CriterionType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Boolean = 'boolean';
    case Range = 'range';
    case Date = 'date';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Văn bản',
            self::Number => 'Số',
            self::Select => 'Chọn 1',
            self::MultiSelect => 'Chọn nhiều',
            self::Boolean => 'Có/Không',
            self::Range => 'Khoảng giá trị',
            self::Date => 'Ngày',
        };
    }

    /** §3.4 — chỉ 2 type này cần quản lý criterion_options con. */
    public function hasOptions(): bool
    {
        return $this === self::Select || $this === self::MultiSelect;
    }
}
