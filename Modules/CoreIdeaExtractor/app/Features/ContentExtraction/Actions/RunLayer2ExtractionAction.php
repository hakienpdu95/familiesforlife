<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Tự động hoá "Layer 2" (spec/CoreIdeaExtractor.md §6/§12.3) — CHỈ chạy khi người dùng bấm nút
 * "Chạy Layer 2 bằng AI" thủ công (KHÔNG tự động sau Layer 1), theo đúng yêu cầu 2026-07-28: cần
 * kiểm soát chi phí + để người dùng tối ưu nội dung kỹ thuật (chỉnh sửa Layer 1/ngữ cảnh chuyên
 * mục) trước khi thực sự tốn tiền gọi AI.
 *
 * QUAN TRỌNG: tái dùng NGUYÊN VĂN prompt đã được tinh chỉnh qua 15 phiên bản ở
 * copyPromptForAi() (JS, index.blade.php — TOP/MIDDLE/BOTTOM: persona + Category Content
 * Foundation + JSON Layer 1 đầy đủ + 3 bước brainstorm/lọc/xuất bảng) — $prompt nhận NGUYÊN VĂN
 * chuỗi client đã build sẵn, KHÔNG build lại logic prompt ở PHP (tránh trùng lặp + lệch pha giữa
 * 2 nơi, và không mất công sức tinh chỉnh đã kiểm chứng thật với Claude/Grok qua nhiều vòng).
 *
 * responseSchema chỉ có 1 field `markdown_output` — khớp ĐÚNG định dạng "2 bảng Markdown" mà
 * BOTTOM của prompt đã yêu cầu từ trước (BƯỚC 3), không cần đổi bất kỳ wording nào của prompt
 * hiện có. AnthropicProvider/OpenAIProvider BẮT BUỘC structured JSON output cho mọi call (xem
 * app/Services/AI/Providers/*.php) nên không thể xin markdown thô trực tiếp — bọc 1 field string
 * là cách đơn giản nhất để lấy lại đúng nội dung 2 bảng mà không phải thiết kế schema chi tiết
 * theo từng cột bảng (rủi ro lệch nếu AI đổi tên cột/thêm cột theo yêu cầu editorial sau này).
 */
class RunLayer2ExtractionAction
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
        private readonly CheckCoreIdeaAiBudgetAction $budget,
    ) {}

    /** @return array{markdown_output: string, model_used: string, cost_usd: float} */
    public function handle(Organization $organization, string $prompt): array
    {
        $config   = $organization->ai_provider_config ?? config('ai.default');
        $provider = $config['provider'] ?? 'anthropic';
        $model    = $config['model'] ?? config('ai.default.model');

        $estimatedInputTokens = (int) ceil(mb_strlen($prompt) / 4);
        $maxOutputTokens      = (int) config('core_idea_extractor.layer2.max_output_tokens', 4096);

        $this->budget->ensureWithinBudget($organization, $estimatedInputTokens, $maxOutputTokens, $provider, $model);

        // Gọi AI 5-30s trong request đồng bộ (đúng yêu cầu "nút bấm thủ công", không phải job nền
        // như Aicem) — nới execution time limit của PHP để tránh bị cắt giữa chừng nếu model
        // opus/context dài phản hồi chậm hơn mức max_execution_time mặc định của môi trường.
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

        // Log riêng cho dashboard Aicem ("Tổng quan") cộng đúng chi phí Layer 2 vào
        // `cost_this_month` — KHÔNG dùng cho việc chặn hạn mức (xem $this->budget ở trên và
        // comment đầu file cie_layer2_runs migration để hiểu vì sao cần bảng riêng).
        DB::table('cie_layer2_runs')->insert([
            'organization_id' => $organization->id,
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
