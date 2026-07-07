<?php

namespace Modules\Post\Enums;

enum ArticleFormat: string
{
    case Article    = 'article';
    case Video      = 'video';
    case Activity   = 'activity';
    case Tip        = 'tip';
    case StepByStep = 'step_by_step';

    public function label(): string
    {
        return match ($this) {
            self::Article    => 'Bài viết',
            self::Video      => 'Video',
            self::Activity   => 'Hoạt động',
            self::Tip        => 'Mẹo hay',
            self::StepByStep => 'Hướng dẫn từng bước',
        };
    }
}
