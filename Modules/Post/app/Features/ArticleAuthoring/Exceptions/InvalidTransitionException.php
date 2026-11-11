<?php

namespace Modules\Post\Features\ArticleAuthoring\Exceptions;

class InvalidTransitionException extends \RuntimeException
{
    public function __construct(public readonly string $from, public readonly string $to)
    {
        parent::__construct(__('Không thể chuyển trạng thái từ ":from" sang ":to".', ['from' => $from, 'to' => $to]));
    }
}
