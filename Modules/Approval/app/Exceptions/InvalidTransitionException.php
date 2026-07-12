<?php

namespace Modules\Approval\Exceptions;

class InvalidTransitionException extends \RuntimeException
{
    public function __construct(public readonly string $from, public readonly string $to)
    {
        parent::__construct("Không thể chuyển trạng thái duyệt từ \"{$from}\" sang \"{$to}\".");
    }
}
