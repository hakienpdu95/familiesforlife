<?php

namespace Modules\AiCopilot\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class QuotaExceededException extends HttpException
{
    public function __construct(public readonly string $quotaSlug)
    {
        parent::__construct(
            statusCode: 402,
            message:    "Đã hết quota [{$quotaSlug}] trong tháng này. Vui lòng nâng cấp gói.",
        );
    }
}
