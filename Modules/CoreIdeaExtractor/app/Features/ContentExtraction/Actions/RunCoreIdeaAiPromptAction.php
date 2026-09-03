<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use App\Services\AI\AiBudgetGuard;
use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Runner AI dùng chung cho 2 tính năng mở rộng CoreIdeaExtractor (spec/content.md mục A+B,
 * 2026-07-30): "Tóm tắt nội dung" (kind=summarization) và "Tái cấu trúc nội dung" (kind=rewrite).
 * Cùng triết lý với RunLayer2ExtractionAction (prompt build sẵn NGUYÊN VĂN ở client, PHP chỉ gọi
 * AI + kiểm tra ngân sách + ghi audit) nhưng tách action riêng thay vì tái dùng thẳng class đó —
 * RunLayer2ExtractionAction đã có docblock/lịch sử tinh chỉnh riêng cho luồng sinh ý tưởng, gộp
 * chung sẽ làm rối ngữ nghĩa "Layer 2" trong khi 2 tính năng mới không liên quan tới Category
 * Content Foundation/existing-articles/4-tiêu-chí như luồng đó.
 *
 * responseSchema chỉ 1 field `markdown_output` — lý do y hệt RunLayer2ExtractionAction:
 * AnthropicProvider/OpenAIProvider bắt buộc structured JSON output, bọc 1 field string là cách
 * đơn giản nhất lấy lại markdown thô mà không cần thiết kế schema chi tiết theo từng mục (rủi ro
 * lệch nếu đổi số nền tảng/độ dài yêu cầu trong prompt sau này).
 */
class RunCoreIdeaAiPromptAction
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
     * 2026-08 — trước đây temperature=0.3 CỨNG cho cả 2 kind, dù bản chất khác nhau: `summarization`
     * cần bám sát tuyệt đối nguồn (KHÔNG bịa số liệu/sự kiện — hallucinate ở đây nguy hiểm hơn hẳn
     * so với thiếu sáng tạo), nên hạ xuống 0.2. `rewrite` cần đổi giọng văn/độ dài khác nhau cho 3
     * nền tảng (Facebook/LinkedIn/Twitter) — vẫn phải bám ý chính của nguồn, nhưng cần nhiệt độ nhích
     * lên (0.4) để 3 phiên bản thực sự khác biệt về giọng, không chỉ đổi vài từ.
     */
    private const TEMPERATURE_BY_KIND = [
        'summarization' => 0.2,
        'rewrite' => 0.4,
    ];

    private const DEFAULT_TEMPERATURE = 0.3;

    public function __construct(
        private readonly AIProviderManager $aiProviderManager,
        private readonly AiBudgetGuard $budget,
    ) {}

    /** @return array{markdown_output: string, model_used: string, cost_usd: float} */
    public function handle(Organization $organization, string $prompt, string $kind, int $maxOutputTokens): array
    {
        $config = $organization->ai_provider_config ?? config('ai.default');
        $provider = $config['provider'] ?? 'anthropic';
        $model = $config['model'] ?? config('ai.default.model');

        $estimatedInputTokens = (int) ceil(mb_strlen($prompt) / 4);

        $this->budget->ensureWithinBudget($organization, $estimatedInputTokens, $maxOutputTokens, $provider, $model);

        // Gọi AI đồng bộ trong request (nút bấm thủ công, không phải job nền) — nới execution
        // time limit giống RunLayer2ExtractionAction để tránh bị cắt giữa chừng nếu model phản
        // hồi chậm.
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

        // Dùng chung bảng cie_layer2_runs với Layer 2 gốc — GetAicemUsageStatsHandler chỉ
        // SUM(cost_usd) theo tháng, không lọc theo `kind`, nên cộng đúng vào cost_this_month bất
        // kể nguồn gốc; cột `kind` chỉ phục vụ audit/tách chi phí theo tính năng sau này.
        DB::table('cie_layer2_runs')->insert([
            'organization_id' => $organization->id,
            'kind' => $kind,
            'cost_usd' => $response->costUsd,
            'model_used' => $response->modelUsed,
            'created_at' => now(),
        ]);

        $decoded = json_decode($response->content, associative: true);

        return [
            'markdown_output' => $decoded['markdown_output'] ?? $response->content,
            'model_used' => $response->modelUsed,
            'cost_usd' => $response->costUsd,
        ];
    }
}
