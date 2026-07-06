<?php
namespace Modules\WorkflowAutomation\Enums;

enum OperatorType: string
{
    case Eq         = '=';
    case Neq        = '!=';
    case Gt         = '>';
    case Gte        = '>=';
    case Lt         = '<';
    case Lte        = '<=';
    case In         = 'in';
    case NotIn      = 'not_in';
    case Contains   = 'contains';
    case StartsWith = 'starts_with';
    case IsEmpty    = 'is_empty';
    case IsNotEmpty = 'is_not_empty';

    public function applicableTypes(): array {
        return match($this) {
            self::Eq, self::Neq                                                => ['string','integer','decimal','boolean'],
            self::Gt, self::Gte, self::Lt, self::Lte                           => ['integer','decimal'],
            self::In, self::NotIn, self::Contains, self::StartsWith,
            self::IsEmpty, self::IsNotEmpty                                    => ['string'],
        };
    }

    public function label(): string {
        return match($this) {
            self::Eq         => 'Bằng',
            self::Neq        => 'Khác',
            self::Gt         => 'Lớn hơn',
            self::Gte        => 'Lớn hơn hoặc bằng',
            self::Lt         => 'Nhỏ hơn',
            self::Lte        => 'Nhỏ hơn hoặc bằng',
            self::In         => 'Thuộc danh sách (|)',
            self::NotIn      => 'Không thuộc danh sách',
            self::Contains   => 'Chứa',
            self::StartsWith => 'Bắt đầu bằng',
            self::IsEmpty    => 'Trống',
            self::IsNotEmpty => 'Không trống',
        };
    }
}
