<?php

namespace Modules\Post\Enums;

enum ArticleFormat: string
{
    case Article = 'article';
    case Video = 'video';
    case Activity = 'activity';
    case Tip = 'tip';
    case StepByStep = 'step_by_step';
    /** Không có nội dung riêng — chỉ dẫn link, click vào là redirect thẳng ra redirect_url. */
    case Redirect = 'redirect';

    public function label(): string
    {
        return match ($this) {
            self::Article => __('Bài viết'),
            self::Video => 'Video',
            self::Activity => __('Hoạt động'),
            self::Tip => __('Mẹo hay'),
            self::StepByStep => __('Hướng dẫn từng bước'),
            self::Redirect => __('Liên kết ngoài (redirect)'),
        };
    }
}
