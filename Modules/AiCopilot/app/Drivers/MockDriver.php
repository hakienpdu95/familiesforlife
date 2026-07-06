<?php

namespace Modules\AiCopilot\Drivers;

use Modules\AiCopilot\Drivers\Contracts\AiDriverContract;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionRequest;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionResult;

class MockDriver implements AiDriverContract
{
    private string $fixedContent;

    public function __construct(string $fixedContent = '[Mock AI response]')
    {
        $this->fixedContent = $fixedContent;
    }

    public function complete(AiCompletionRequest $req): AiCompletionResult
    {
        return new AiCompletionResult(
            content:      $this->fixedContent,
            finishReason: 'end_turn',
            inputTokens:  $this->estimateTokens($req->systemPrompt . $req->userMessage),
            outputTokens: $this->estimateTokens($this->fixedContent),
            totalTokens:  $this->estimateTokens($req->systemPrompt . $req->userMessage . $this->fixedContent),
            costUsd:      0.0,
            durationMs:   10,
        );
    }

    public function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function pricing(string $model): array
    {
        return ['input' => 0.0, 'output' => 0.0];
    }
}
