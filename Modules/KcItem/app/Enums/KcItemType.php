<?php

namespace Modules\KcItem\Enums;

enum KcItemType: string
{
    case Document  = 'document';
    case Sop       = 'sop';
    case Video     = 'video';
    case Form      = 'form';
    case Faq       = 'faq';
    case CaseStudy = 'case_study';
    case Policy    = 'policy';

    public function label(): string
    {
        return match ($this) {
            self::Document  => 'Tài liệu',
            self::Sop       => 'SOP',
            self::Video     => 'Video',
            self::Form      => 'Biểu mẫu',
            self::Faq       => 'FAQ',
            self::CaseStudy => 'Case Study',
            self::Policy    => 'Policy/Guideline',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Document  => 'ti-file-text',
            self::Sop       => 'ti-list-check',
            self::Video     => 'ti-video',
            self::Form      => 'ti-forms',
            self::Faq       => 'ti-help-circle',
            self::CaseStudy => 'ti-bulb',
            self::Policy    => 'ti-scale',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'text' => $case->label()],
            self::cases()
        );
    }
}
