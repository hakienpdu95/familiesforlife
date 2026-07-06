<?php

namespace Modules\AiCopilot\Drivers\Contracts;

use Modules\AiCopilot\Drivers\DTOs\AiCompletionRequest;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionResult;

interface AiDriverContract
{
    public function complete(AiCompletionRequest $request): AiCompletionResult;

    public function estimateTokens(string $text): int;

    /** Giá per 1M tokens — ['input' => float, 'output' => float] */
    public function pricing(string $model): array;
}
