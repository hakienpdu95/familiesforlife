<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderContract;
use App\Services\AI\AIRequestOptions;
use App\Services\AI\AIResponse;
use App\Services\AI\Exceptions\AIProviderConfigException;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;

final class OpenAIProvider implements AIProviderContract
{
    public function __construct(private readonly ClientContract $client) {}

    public function complete(array $messages, AIRequestOptions $options): AIResponse
    {
        // Bỏ key 'cacheable' (BuildPromptAction gắn cho message system — chỉ Anthropic dùng để
        // build cache_control, mục 8.7/Phase 6) trước khi gửi — OpenAI không hiểu field lạ này,
        // và caching của OpenAI vốn tự động/không cần đánh dấu thủ công (mục 15, đánh giá sau).
        $cleanMessages = array_map(
            fn (array $m) => ['role' => $m['role'], 'content' => $m['content']],
            $messages
        );

        try {
            $response = $this->client->chat()->create([
                'model'           => $options->model,
                'messages'        => $cleanMessages,
                'temperature'     => $options->temperature,
                'max_tokens'      => $options->maxTokens,
                'response_format' => [
                    'type'        => 'json_schema',
                    'json_schema' => [
                        'name'   => 'aicem_suggestions',
                        'strict' => true,
                        'schema' => $options->responseSchema,
                    ],
                ],
            ]);
        } catch (ErrorException $e) {
            // OpenAI SDK dùng CHUNG 1 exception class cho mọi lỗi HTTP — phải tự phân biệt qua
            // status code (không có class riêng theo 401/400 như Anthropic). 401/400 không nên
            // retry (mục 8.8); 429/5xx để nguyên cho job tự retry bình thường.
            if (in_array($e->getStatusCode(), [400, 401], true)) {
                throw new AIProviderConfigException(sprintf(
                    'Lỗi cấu hình OpenAI (HTTP %d %s): %s. Vào "Cấu hình AICEM" (System Admin) để kiểm tra '
                    . 'lại API key/model, hoặc set AI_DEFAULT_API_KEY trong .env.',
                    $e->getStatusCode(),
                    $e->getStatusCode() === 401 ? 'Unauthorized' : 'Bad Request',
                    $e->getErrorMessage(),
                ), previous: $e);
            }

            throw $e;
        }

        return new AIResponse(
            content:      $response->choices[0]->message->content ?? '',
            modelUsed:    $response->model,
            inputTokens:  $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens ?? 0,
            costUsd:      0.0,
            raw:          $response->toArray(),
        );
    }
}
