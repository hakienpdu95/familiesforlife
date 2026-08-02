<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Tương đương RunLayer2ExtractionAction bên CoreIdeaExtractor — tách riêng khỏi
 * RunVideoIdeaAiPromptAction (generic, dùng cho Tiêu đề/Hook/Shorts) cùng lý do CoreIdeaExtractor
 * đã tách RunLayer2ExtractionAction khỏi RunCoreIdeaAiPromptAction: giữ ngữ nghĩa "Layer 2" (sinh ý
 * tưởng) không bị rối khi gộp chung với các tính năng khác. QUAN TRỌNG: tái dùng NGUYÊN VĂN prompt
 * đã build sẵn ở client (buildLayer2PromptText() trong index.blade.php) — $prompt nhận NGUYÊN VĂN
 * chuỗi client đã build, KHÔNG build lại logic prompt ở PHP.
 *
 * responseSchema chỉ 1 field `markdown_output` — AnthropicProvider/OpenAIProvider bắt buộc
 * structured JSON output, bọc 1 field string là cách đơn giản nhất lấy lại markdown thô.
 */
class RunVideoIdeaLayer2Action
{
    private const RESPONSE_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'markdown_output' => [
                'type' => 'string',
                'description' => 'Đúng 2 bảng Markdown theo yêu cầu ở BƯỚC 3 của prompt — không thêm giải thích/mở đầu/kết luận nào khác ngoài 2 bảng.',
            ],
        ],
        'required' => ['markdown_output'],
    ];

    public function __construct(
        private readonly AIProviderManager $aiProviderManager,
        private readonly CheckVideoIdeaAiBudgetAction $budget,
    ) {}

    /** @return array{markdown_output: string, model_used: string, cost_usd: float} */
    public function handle(Organization $organization, string $prompt): array
    {
        $config   = $organization->ai_provider_config ?? config('ai.default');
        $provider = $config['provider'] ?? 'anthropic';
        $model    = $config['model'] ?? config('ai.default.model');

        $estimatedInputTokens = (int) ceil(mb_strlen($prompt) / 4);
        $maxOutputTokens      = (int) config('video_idea_extractor.layer2.max_output_tokens', 4096);

        $this->budget->ensureWithinBudget($organization, $estimatedInputTokens, $maxOutputTokens, $provider, $model);

        // Gọi AI đồng bộ trong request (nút bấm thủ công, không phải job nền) — nới execution
        // time limit để tránh bị cắt giữa chừng nếu model phản hồi chậm.
        set_time_limit(120);

        $options = new AIRequestOptions(
            model: $model,
            responseSchema: self::RESPONSE_SCHEMA,
            temperature: 0.3,
            maxTokens: $maxOutputTokens,
        );

        $response = $this->aiProviderManager->complete($organization, [
            ['role' => 'user', 'content' => $prompt],
        ], $options);

        $this->budget->recordActualCost($organization, $response->costUsd);

        DB::table('video_idea_extractor_layer2_runs')->insert([
            'organization_id' => $organization->id,
            'kind'            => 'layer2',
            'cost_usd'        => $response->costUsd,
            'model_used'      => $response->modelUsed,
            'created_at'      => now(),
        ]);

        $decoded = json_decode($response->content, associative: true);

        return [
            'markdown_output' => $decoded['markdown_output'] ?? $response->content,
            'model_used'      => $response->modelUsed,
            'cost_usd'        => $response->costUsd,
        ];
    }
}
