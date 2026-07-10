<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Lỗi cấu hình provider (API key sai, schema bị reject...) — không nên retry,
 * vì lặp lại y hệt ở lần thử sau. Khác với lỗi timeout/429 (nên để queue retry
 * xử lý bình thường). Xem AICEM_Technical_Specification.md mục 8.8.
 */
class AIProviderConfigException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
