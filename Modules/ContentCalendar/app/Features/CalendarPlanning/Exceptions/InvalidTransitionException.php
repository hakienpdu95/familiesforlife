<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Exceptions;

/** Cùng khuôn Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException. */
class InvalidTransitionException extends \RuntimeException
{
    public function __construct(public readonly string $from, public readonly string $to)
    {
        parent::__construct("Không thể chuyển trạng thái kế hoạch từ \"{$from}\" sang \"{$to}\".");
    }
}
