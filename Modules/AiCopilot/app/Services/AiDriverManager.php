<?php

namespace Modules\AiCopilot\Services;

use Modules\AiCopilot\Drivers\ClaudeDriver;
use Modules\AiCopilot\Drivers\Contracts\AiDriverContract;
use Modules\AiCopilot\Drivers\MockDriver;
use Modules\AiCopilot\Drivers\OpenAiDriver;

class AiDriverManager
{
    private array $resolved = [];

    public function driver(string $provider): AiDriverContract
    {
        return $this->resolved[$provider] ??= match($provider) {
            'claude' => new ClaudeDriver(config('ai_copilot.providers.claude.api_key', '')),
            'openai' => new OpenAiDriver(config('ai_copilot.providers.openai.api_key', '')),
            'mock'   => new MockDriver(),
            default  => throw new \InvalidArgumentException("Unknown AI provider: [{$provider}]"),
        };
    }
}
