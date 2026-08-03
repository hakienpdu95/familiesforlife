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

    /**
     * 2026-08 — trước đây temperature=0.3 CỨNG cho MỌI kind, dù prompt yêu cầu độ đa dạng sáng tạo
     * khác hẳn nhau: `titles` đòi "6 KIỂU khác nhau", `hooks` đòi "5 kiểu tâm lý khác nhau" (cần
     * nhiệt độ CAO hơn để model thực sự tạo ra sự khác biệt giữa các biến thể, không lặp ý tưởng
     * dưới lốt câu chữ khác nhau — 0.3 quá thấp cho việc này); ngược lại `polish` yêu cầu bám nguyên
     * văn bản nháp, KHÔNG được sáng tạo thêm chi tiết mới, và `shorts`/`outline` phải TRÍCH ĐÚNG
     * đoạn/mốc thời gian có thật trong transcript (không phải bịa mới) — 2 nhóm cần nhiệt độ THẤP
     * để giảm rủi ro hallucinate. `cta` ở giữa: cần vài biến thể nhưng vẫn phải bám giá trị cụ thể
     * vừa nêu trong nội dung, không tự do sáng tạo như titles/hooks.
     */
    private const TEMPERATURE_BY_KIND = [
        'titles'  => 0.7,
        'hooks'   => 0.7,
        'shorts'  => 0.3,
        'outline' => 0.3,
        'cta'     => 0.5,
        'polish'  => 0.2,
    ];

    private const DEFAULT_TEMPERATURE = 0.3;

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
            temperature: self::TEMPERATURE_BY_KIND[$kind] ?? self::DEFAULT_TEMPERATURE,
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
