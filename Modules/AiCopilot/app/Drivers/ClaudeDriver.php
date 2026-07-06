<?php

namespace Modules\AiCopilot\Drivers;

use Anthropic\Client;
use Modules\AiCopilot\Drivers\Contracts\AiDriverContract;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionRequest;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionResult;

class ClaudeDriver implements AiDriverContract
{
    private Client $client;

    public function __construct(string $apiKey)
    {
        $this->client = new Client(apiKey: $apiKey);
    }

    public function complete(AiCompletionRequest $req): AiCompletionResult
    {
        $start = microtime(true);

        $response = $this->client->messages->create(
            maxTokens:   $req->maxTokens,
            messages:    [['role' => 'user', 'content' => $req->userMessage]],
            model:       $req->model,
            system:      $req->systemPrompt,
            temperature: $req->temperature,
        );

        $inputTokens  = $response->usage->inputTokens;
        $outputTokens = $response->usage->outputTokens;

        [$inPrice, $outPrice] = array_values($this->pricing($req->model));

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new AiCompletionResult(
            content:      $text,
            finishReason: $response->stopReason ?? 'end_turn',
            inputTokens:  $inputTokens,
            outputTokens: $outputTokens,
            totalTokens:  $inputTokens + $outputTokens,
            costUsd:      ($inputTokens * $inPrice + $outputTokens * $outPrice) / 1_000_000,
            durationMs:   (int) ((microtime(true) - $start) * 1000),
        );
    }

    public function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function pricing(string $model): array
    {
        return match(true) {
            str_contains($model, 'opus')   => ['input' => 15.00, 'output' => 75.00],
            str_contains($model, 'sonnet') => ['input' =>  3.00, 'output' => 15.00],
            default                        => ['input' =>  0.25, 'output' =>  1.25], // haiku
        };
    }
}
