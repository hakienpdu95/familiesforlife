<?php

namespace Modules\AiCopilot\Drivers;

use OpenAI;
use OpenAI\Client;
use Modules\AiCopilot\Drivers\Contracts\AiDriverContract;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionRequest;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionResult;

class OpenAiDriver implements AiDriverContract
{
    private Client $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function complete(AiCompletionRequest $req): AiCompletionResult
    {
        $start = microtime(true);

        $response = $this->client->chat()->create([
            'model'       => $req->model,
            'temperature' => $req->temperature,
            'max_tokens'  => $req->maxTokens,
            'messages'    => [
                ['role' => 'system', 'content' => $req->systemPrompt],
                ['role' => 'user',   'content' => $req->userMessage],
            ],
        ]);

        $choice       = $response->choices[0];
        $inputTokens  = $response->usage->promptTokens;
        $outputTokens = $response->usage->completionTokens;

        [$inPrice, $outPrice] = array_values($this->pricing($req->model));

        return new AiCompletionResult(
            content:      $choice->message->content ?? '',
            finishReason: $choice->finishReason ?? 'stop',
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
            str_contains($model, 'gpt-4o')      => ['input' => 2.50, 'output' => 10.00],
            str_contains($model, 'gpt-4o-mini') => ['input' => 0.15, 'output' =>  0.60],
            str_contains($model, 'gpt-4')       => ['input' => 30.00, 'output' => 60.00],
            default                             => ['input' => 0.50,  'output' =>  1.50],
        };
    }
}
