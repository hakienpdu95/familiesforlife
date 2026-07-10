<?php

namespace App\Services\AI\Providers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use Anthropic\Messages\TextBlockParam;
use App\Services\AI\AIProviderContract;
use App\Services\AI\AIRequestOptions;
use App\Services\AI\AIResponse;
use App\Services\AI\Exceptions\AIProviderConfigException;

final class AnthropicProvider implements AIProviderContract
{
    public function __construct(private readonly Client $client) {}

    public function complete(array $messages, AIRequestOptions $options): AIResponse
    {
        [$system, $rest] = $this->splitSystemMessages($messages);

        try {
            $message = $this->client->messages->create(
                model:       $options->model,
                maxTokens:   $options->maxTokens,
                temperature: $options->temperature,
                system:      $system,
                messages:    $rest,
                outputConfig: OutputConfig::with(
                    format: JSONOutputFormat::with(schema: $options->responseSchema)
                ),
            );
        } catch (AuthenticationException $e) {
            // 401 — API key sai/hết hạn/chưa cấu hình. KHÔNG nên retry (mục 8.8): lần sau vẫn
            // sai y hệt, retry chỉ tốn thời gian chờ backoff vô ích.
            throw new AIProviderConfigException(
                'API key Anthropic không hợp lệ hoặc chưa được cấu hình (HTTP 401 Unauthorized). '
                . 'Vào "Cấu hình AICEM" (System Admin) để nhập API key thật, hoặc set AI_DEFAULT_API_KEY trong .env.',
                previous: $e,
            );
        } catch (PermissionDeniedException $e) {
            throw new AIProviderConfigException(
                'API key Anthropic không có quyền truy cập model "' . $options->model . '" (HTTP 403 Forbidden). '
                . 'Kiểm tra lại quyền của API key hoặc tên model trong "Cấu hình AICEM".',
                previous: $e,
            );
        } catch (BadRequestException $e) {
            // 400 — thường do output_contract/schema sai cấu trúc (mục 8.3), không phải lỗi mạng.
            throw new AIProviderConfigException(
                'Yêu cầu gửi tới Anthropic không hợp lệ (HTTP 400 Bad Request): ' . $e->getMessage()
                . '. Có thể do context template/output schema cấu hình sai — kiểm tra lại workflow đang chạy.',
                previous: $e,
            );
        }

        $textBlock = $message->content[0];

        return new AIResponse(
            content:      $textBlock->text,
            modelUsed:    $message->model,
            inputTokens:  $message->usage->inputTokens,
            outputTokens: $message->usage->outputTokens,
            costUsd:      0.0,
            raw:          $message->toArray(),
            cacheCreationInputTokens: $message->usage->cacheCreationInputTokens ?? 0,
            cacheReadInputTokens:     $message->usage->cacheReadInputTokens ?? 0,
        );
    }

    /**
     * Gộp mọi message role=system (Anthropic: system là param riêng, không nằm trong messages[]).
     *
     * Phase 6 (mục 8.7/15) — prompt caching: BuildPromptAction đánh dấu message system chứa khối
     * DNA/knowledge_document bằng `cacheable => true` vì nội dung này lặp lại gần như nguyên văn
     * qua mọi lần chạy AI của cùng 1 Organization+workflow. Anthropic CHỈ cache được khi `system`
     * là MẢNG content-block (TextBlockParam[]), không cache được khi `system` là string thuần —
     * xác nhận trực tiếp từ SDK (`SystemShape = string|list<TextBlockParam>`). Do đó: có ít nhất 1
     * message cacheable → build mảng TextBlockParam (gắn CacheControlEphemeral trên đúng block đó);
     * không có gì cacheable → giữ nguyên hành vi cũ (gộp thành 1 string) để không đổi behavior cho
     * các workflow/template chưa cần cache.
     *
     * @return array{0: string|array<int, TextBlockParam>, 1: array}
     */
    private function splitSystemMessages(array $messages): array
    {
        $systemMessages = array_values(array_filter($messages, fn ($m) => $m['role'] === 'system'));
        $rest           = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        $hasCacheable = ! empty(array_filter($systemMessages, fn ($m) => ($m['cacheable'] ?? false) === true));

        if (! $hasCacheable) {
            return [implode("\n\n", array_column($systemMessages, 'content')), $rest];
        }

        $blocks = array_map(
            fn (array $m) => ($m['cacheable'] ?? false)
                ? TextBlockParam::with(text: $m['content'], cacheControl: CacheControlEphemeral::with())
                : TextBlockParam::with(text: $m['content']),
            $systemMessages
        );

        return [$blocks, $rest];
    }
}
