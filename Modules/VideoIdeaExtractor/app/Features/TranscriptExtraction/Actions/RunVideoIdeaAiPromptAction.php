<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Runner AI dùng chung cho 3 tính năng mở rộng của VideoIdeaExtractor: "Tiêu đề & Thumbnail"
 * (kind=titles), "Hook mở đầu" (kind=hooks), "Ý tưởng Shorts" (kind=shorts) — tương đương
 * RunCoreIdeaAiPromptAction bên CoreIdeaExtractor. Tách riêng khỏi RunVideoIdeaLayer2Action (action
 * đó có docblock/lịch sử tinh chỉnh riêng cho luồng sinh ý tưởng) — 3 tính năng này KHÔNG liên quan
 * tới việc "sinh ý tưởng bài/video mới" (Layer 2), mà là các bước SAU KHI đã chọn 1 chủ đề, thao
 * tác trên ĐÚNG 1 video đã trích xuất.
 *
 * responseSchema chỉ 1 field `markdown_output` — cùng lý do RunVideoIdeaLayer2Action: bọc 1 field
 * string là cách đơn giản nhất lấy lại markdown thô mà không cần thiết kế schema chi tiết theo
 * từng cột bảng (rủi ro lệch nếu đổi số cột/độ dài yêu cầu trong prompt sau này).
 */
class RunVideoIdeaAiPromptAction
{
    private const RESPONSE_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'markdown_output' => [
                'type' => 'string',
                'description' => 'Nội dung Markdown theo đúng định dạng đã yêu cầu trong prompt — không thêm giải thích/mở đầu/kết luận nào khác.',
            ],
        ],
        'required' => ['markdown_output'],
    ];

    public function __construct(
        private readonly AIProviderManager $aiProviderManager,
        private readonly CheckVideoIdeaAiBudgetAction $budget,
    ) {}

    /** @return array{markdown_output: string, model_used: string, cost_usd: float} */
    public function handle(Organization $organization, string $prompt, string $kind, int $maxOutputTokens): array
    {
        $config   = $organization->ai_provider_config ?? config('ai.default');
        $provider = $config['provider'] ?? 'anthropic';
        $model    = $config['model'] ?? config('ai.default.model');

        $estimatedInputTokens = (int) ceil(mb_strlen($prompt) / 4);

        $this->budget->ensureWithinBudget($organization, $estimatedInputTokens, $maxOutputTokens, $provider, $model);

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

        // Dùng chung bảng video_idea_extractor_layer2_runs với Layer 2 gốc — GetAicemUsageStatsHandler
        // chỉ SUM(cost_usd) theo tháng, không lọc theo `kind`, nên cộng đúng vào cost_this_month bất
        // kể nguồn gốc; cột `kind` chỉ phục vụ audit/tách chi phí theo tính năng sau này.
        DB::table('video_idea_extractor_layer2_runs')->insert([
            'organization_id' => $organization->id,
            'kind'            => $kind,
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
