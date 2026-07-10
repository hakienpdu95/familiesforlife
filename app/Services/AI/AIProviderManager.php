<?php

namespace App\Services\AI;

use App\Services\AI\Exceptions\UnsupportedAIProviderException;
use App\Shared\Tenancy\Models\Organization;

final class AIProviderManager
{
    /**
     * Test-only fake queue — set qua self::fake(), tương tự idiom Http::fake()/Queue::fake().
     * Không dùng Mockery/test double vì AIProviderManager không phải interface và được đánh dấu
     * final; queue tĩnh này để complete() trả response giả theo thứ tự gọi mà không đụng SDK thật,
     * không cần network (spec/AICEM_Technical_Specification.md mục 8.9).
     *
     * @var AIResponse[]|null
     */
    private static ?array $fakeQueue = null;

    /** @param AIResponse[] $responses */
    public static function fake(array $responses): void
    {
        self::$fakeQueue = $responses;
    }

    public static function fakeReset(): void
    {
        self::$fakeQueue = null;
    }

    public function complete(?Organization $organization, array $messages, AIRequestOptions $options): AIResponse
    {
        if (self::$fakeQueue !== null) {
            return array_shift(self::$fakeQueue)
                ?? throw new \RuntimeException('AIProviderManager::fake() queue rỗng — không còn response giả để trả.');
        }

        $config   = $organization?->ai_provider_config ?? config('ai.default');
        $provider = $this->makeProvider($config);

        $response = $provider->complete($messages, $options);

        $costUsd = CostCalculator::calculate(
            $config['provider'],
            $response->modelUsed,
            $response->inputTokens,
            $response->outputTokens,
            $response->cacheCreationInputTokens,
            $response->cacheReadInputTokens,
        );

        return new AIResponse(
            content:      $response->content,
            modelUsed:    $response->modelUsed,
            inputTokens:  $response->inputTokens,
            outputTokens: $response->outputTokens,
            costUsd:      $costUsd,
            raw:          $response->raw,
            cacheCreationInputTokens: $response->cacheCreationInputTokens,
            cacheReadInputTokens:     $response->cacheReadInputTokens,
        );
    }

    private function makeProvider(array $config): AIProviderContract
    {
        return match ($config['provider']) {
            'openai'    => new Providers\OpenAIProvider(\OpenAI::client($config['api_key'] ?? config('openai.api_key'))),
            'anthropic' => new Providers\AnthropicProvider(new \Anthropic\Client(apiKey: $config['api_key'] ?? config('services.anthropic.api_key'))),
            default     => throw new UnsupportedAIProviderException($config['provider']),
        };
    }
}
