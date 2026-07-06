<?php

namespace Modules\Survey\Enums;

enum FieldType: int
{
    case Text     = 1;
    case Textarea = 2;
    case Number   = 3;
    case Select   = 4;
    case Radio    = 5;
    case Checkbox = 6;
    case Rating   = 7;
    case Date     = 8;
    case Boolean  = 9;
    case Matrix   = 10;
    case Ranking  = 11;
    case Nps      = 12;

    public function valueKind(): ValueKind
    {
        return match ($this) {
            self::Text                                          => ValueKind::String,
            self::Textarea                                      => ValueKind::Text,
            self::Number, self::Rating, self::Nps               => ValueKind::Number,
            self::Select, self::Radio, self::Checkbox,
            self::Matrix, self::Ranking                         => ValueKind::Option,
            self::Date                                          => ValueKind::Date,
            self::Boolean                                       => ValueKind::Bool,
        };
    }

    public function isChoice(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox]);
    }

    public function isMatrixLike(): bool
    {
        return $this === self::Matrix;
    }

    public function isRanking(): bool
    {
        return $this === self::Ranking;
    }

    public function isNps(): bool
    {
        return $this === self::Nps;
    }

    public function allowsMultiple(): bool
    {
        return $this === self::Checkbox;
    }

    public function label(): string
    {
        return match ($this) {
            self::Text     => 'Văn bản ngắn',
            self::Textarea => 'Văn bản dài',
            self::Number   => 'Số',
            self::Select   => 'Dropdown',
            self::Radio    => 'Radio (chọn 1)',
            self::Checkbox => 'Checkbox (chọn nhiều)',
            self::Rating   => 'Đánh giá sao',
            self::Date     => 'Ngày tháng',
            self::Boolean  => 'Có / Không',
            self::Matrix   => 'Ma trận (grid)',
            self::Ranking  => 'Xếp hạng',
            self::Nps      => 'NPS (0–10)',
        };
    }

    /** True nếu field_type có rule_min / rule_max */
    public function hasRules(): bool
    {
        return in_array($this, [self::Text, self::Textarea, self::Number, self::Rating, self::Nps]);
    }
}
