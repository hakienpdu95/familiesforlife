<?php

namespace Modules\AiCopilot\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class FeatureNotAvailableException extends HttpException
{
    public function __construct(public readonly string $featureSlug)
    {
        parent::__construct(
            statusCode: 402,
            message:    "Tính năng [{$featureSlug}] không khả dụng trong gói hiện tại.",
        );
    }
}
