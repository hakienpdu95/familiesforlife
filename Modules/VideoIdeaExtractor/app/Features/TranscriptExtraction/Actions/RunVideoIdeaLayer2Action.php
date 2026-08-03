<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Exceptions\AiBudgetExceededException;

/**
 * Tương đương RunLayer2ExtractionAction bên CoreIdeaExtractor — tách riêng khỏi
 * RunVideoIdeaAiPromptAction (generic, dùng cho Tiêu đề/Hook/Shorts) cùng lý do CoreIdeaExtractor
 * đã tách RunLayer2ExtractionAction khỏi RunCoreIdeaAiPromptAction: giữ ngữ nghĩa "Layer 2" (sinh ý
 * tưởng) không bị rối khi gộp chung với các tính năng khác. QUAN TRỌNG: tái dùng NGUYÊN VĂN prompt
 * đã build sẵn ở client (buildLayer2PromptText() trong index.blade.php) — $prompt nhận NGUYÊN VĂN
 * chuỗi client đã build, KHÔNG build lại logic prompt ở PHP.
 *
 * 2026-08 — GOAL-BASED LOOP THẬT (tham khảo khái niệm "loop" Anthropic công bố: Goal → Work →
 * Check → Improve → Repeat), thay cho bản cũ "1 lần gọi AI, tin model tự lặp lại Bước 1 trong đầu
 * nếu chưa đủ ý tưởng". Bản cũ không có cách nào PHP biết model có thực sự tự lặp hay không — chỉ
 * đọc được đúng những gì model TRẢ VỀ trong 1 response duy nhất. Giờ PHP tự ĐẾM số ý tưởng đạt cả
 * 4 tiêu chí sau mỗi lần gọi bằng field boolean tường minh (không phải đoán qua đếm dòng bảng
 * Markdown), và tự gọi AI THÊM nếu chưa đủ `layer2.target_idea_count`, tối đa
 * `layer2.max_loop_iterations` lần — mỗi lần nhắc lại các ý ĐÃ đạt để model không lặp. Ranh giới
 * client/server GIỮ NGUYÊN: client vẫn là nơi DUY NHẤT chịu trách nhiệm nội dung ngữ cảnh
 * (buildLayer2PromptText()), action này chỉ thêm phần ĐIỀU KHIỂN VÒNG LẶP + RENDER bảng Markdown
 * cuối cùng từ dữ liệu có cấu trúc (không còn để model tự format bảng — cột 4 tiêu chí luôn "Có"
 * vì chỉ ý ĐÃ ĐẠT mới được render, không cần model tự đảm bảo tính nhất quán format).
 */
class RunVideoIdeaLayer2Action
{
    private const RESPONSE_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'ideas' => [
                'type' => 'array',
                'description' => 'Danh sách ý tưởng ĐẠT cả 4 tiêu chí ở Bước 2 của prompt trong LƯỢT NÀY — KHÔNG liệt kê ý tưởng bị loại, KHÔNG cần tự đảm bảo đủ số lượng mục tiêu (hệ thống sẽ tự yêu cầu thêm nếu cần).',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'idea'               => ['type' => 'string', 'description' => 'Tên/nội dung ý tưởng video, đủ cụ thể để hiểu góc khai thác.'],
                        'format_suggestion'  => ['type' => 'string', 'description' => 'Định dạng gợi ý theo Bước 1: Shorts/video ngắn, video dài, hoặc livestream/Q&A.'],
                        'matches_core_focus'  => ['type' => 'boolean', 'description' => 'Tiêu chí 1 (Bước 2) — khớp trọng tâm nội dung chuyên mục/chủ đề.'],
                        'unique_angle'        => ['type' => 'boolean', 'description' => 'Tiêu chí 2 (Bước 2) — thể hiện góc nhìn độc quyền của chuyên mục.'],
                        'serves_goal'         => ['type' => 'boolean', 'description' => 'Tiêu chí 3 (Bước 2) — phục vụ mục tiêu video đã nêu.'],
                        'fits_audience'       => ['type' => 'boolean', 'description' => 'Tiêu chí 4 (Bước 2) — phù hợp đối tượng khán giả đã nêu.'],
                        'reason'              => ['type' => 'string', 'description' => 'Lý do ngắn (1 câu) vì sao ý tưởng đạt cả 4 tiêu chí.'],
                        'suggested_title'     => ['type' => 'string', 'description' => 'Đề xuất tiêu đề video cho ý tưởng này.'],
                    ],
                    'required' => ['idea', 'format_suggestion', 'matches_core_focus', 'unique_angle', 'serves_goal', 'fits_audience', 'reason', 'suggested_title'],
                ],
            ],
            'category_note' => [
                'type' => ['string', 'null'],
                'description' => 'CHỈ khi chưa chọn chuyên mục (Bước 0 áp dụng): dòng "Chuyên mục phù hợp nhất: [tên]" hoặc "chưa xác định được". Null nếu đã chọn chuyên mục từ trước.',
            ],
            'audience_assumption' => [
                'type' => ['string', 'null'],
                'description' => 'CHỈ khi chưa có mô tả đối tượng khán giả (tiêu chí 4, Bước 2): dòng "Giả định đối tượng: [mô tả ngắn]". Null nếu đã có mô tả đối tượng.',
            ],
            'insufficient_reason' => [
                'type' => ['string', 'null'],
                'description' => 'CHỈ khi đã cố hết sức nhưng KHÔNG thể tạo thêm ý tưởng mới đạt tiêu chí trong lượt này (đã khai thác hết góc nhìn hợp lý từ dữ liệu nguồn): 1 câu lý do ngắn. Null nếu vẫn còn góc nhìn để khai thác.',
            ],
        ],
        'required' => ['ideas'],
    ];

    private const CRITERIA_KEYS = ['matches_core_focus', 'unique_angle', 'serves_goal', 'fits_audience'];

    public function __construct(
        private readonly AIProviderManager $aiProviderManager,
        private readonly CheckVideoIdeaAiBudgetAction $budget,
    ) {}

    /** @return array{markdown_output: string, model_used: string, cost_usd: float, loop_iterations: int} */
    public function handle(Organization $organization, string $prompt): array
    {
        $config   = $organization->ai_provider_config ?? config('ai.default');
        $provider = $config['provider'] ?? 'anthropic';
        $model    = $config['model'] ?? config('ai.default.model');

        $maxOutputTokens = (int) config('video_idea_extractor.layer2.max_output_tokens', 4096);
        $targetCount     = (int) config('video_idea_extractor.layer2.target_idea_count', 8);
        $maxIterations   = max(1, (int) config('video_idea_extractor.layer2.max_loop_iterations', 3));

        // 2026-08 — nâng từ 0.3 lên 0.7: BƯỚC 1 của prompt yêu cầu brainstorm RỘNG 15-20 ý tưởng
        // đa dạng góc nhìn (theo giai đoạn/vấn đề/định dạng khác nhau) — nhiệt độ thấp (0.3) dễ ra
        // nhiều ý tưởng chỉ khác câu chữ nhưng cùng góc khai thác, đi ngược đúng mục tiêu "đa dạng"
        // mà prompt đang yêu cầu. Không đẩy lên cao hơn (VD 0.9-1.0) vì mỗi ý tưởng vẫn phải bám
        // ĐÚNG dữ liệu nguồn thật (không được bịa), 0.7 là điểm cân bằng.
        $options = new AIRequestOptions(
            model: $model,
            responseSchema: self::RESPONSE_SCHEMA,
            temperature: 0.7,
            maxTokens: $maxOutputTokens,
        );

        $accepted           = [];
        $seenNormalized     = [];
        $totalCostUsd       = 0.0;
        $modelUsed          = $model;
        $categoryNote       = null;
        $audienceAssumption = null;
        $insufficientReason = null;
        $currentPrompt      = $prompt;
        $iteration          = 0;

        // Gọi AI đồng bộ trong request (nút bấm thủ công, không phải job nền) — nới execution
        // time limit rộng hơn bản 1-lần-gọi cũ vì giờ có thể chạy tới $maxIterations lượt liên
        // tiếp trong CÙNG 1 request.
        set_time_limit(180);

        while ($iteration < $maxIterations) {
            $iteration++;

            $estimatedInputTokens = (int) ceil(mb_strlen($currentPrompt) / 4);

            try {
                $this->budget->ensureWithinBudget($organization, $estimatedInputTokens, $maxOutputTokens, $provider, $model);
            } catch (AiBudgetExceededException $e) {
                // Hết ngân sách GIỮA vòng lặp (lượt >1) — lượt trước đã tốn tiền thật và tạo ra ý
                // tưởng hợp lệ, KHÔNG ném exception làm mất trắng kết quả đó. Chỉ lượt 1 (chưa có
                // gì để giữ) mới cho exception bay lên như hành vi cũ.
                if ($accepted === []) {
                    throw $e;
                }

                $iteration--;
                $insufficientReason = 'Đã đạt '.count($accepted)." ý tưởng thoả tiêu chí, dừng sớm vì: {$e->getMessage()}";

                break;
            }

            $response = $this->aiProviderManager->complete($organization, [
                ['role' => 'user', 'content' => $currentPrompt],
            ], $options);

            $this->budget->recordActualCost($organization, $response->costUsd);
            $totalCostUsd += $response->costUsd;
            $modelUsed     = $response->modelUsed;

            // Ghi log mỗi LƯỢT gọi thật (không phải mỗi lần bấm nút) — đúng tinh thần cột `kind`
            // hiện có: audit/tách chi phí theo tính năng, giờ 1 lần bấm "Chạy AI" có thể tương
            // ứng NHIỀU dòng nếu vòng lặp chạy >1 lượt.
            DB::table('video_idea_extractor_layer2_runs')->insert([
                'organization_id' => $organization->id,
                'kind'            => 'layer2',
                'cost_usd'        => $response->costUsd,
                'model_used'      => $response->modelUsed,
                'created_at'      => now(),
            ]);

            $decoded = json_decode($response->content, associative: true);
            $ideas   = is_array($decoded['ideas'] ?? null) ? $decoded['ideas'] : [];

            if (! empty($decoded['category_note'])) {
                $categoryNote = (string) $decoded['category_note'];
            }
            if (! empty($decoded['audience_assumption'])) {
                $audienceAssumption = (string) $decoded['audience_assumption'];
            }
            if (! empty($decoded['insufficient_reason'])) {
                $insufficientReason = (string) $decoded['insufficient_reason'];
            }

            $addedThisRound = 0;

            foreach ($ideas as $idea) {
                if (! is_array($idea)) {
                    continue;
                }

                $ideaText   = trim((string) ($idea['idea'] ?? ''));
                $normalized = mb_strtolower($ideaText);

                // Dedup CHÍNH XÁC theo văn bản (sau lowercase/trim) — chặn lưới an toàn cho
                // trường hợp model lặp nguyên văn 1 ý đã có dù đã được nhắc rõ ở buildFollowUpPrompt();
                // KHÔNG bắt được biến thể đổi cách diễn đạt (chấp nhận được — nhắc bằng lời ở
                // followUp prompt là tuyến phòng thủ chính, đây chỉ là lưới phụ).
                if ($ideaText === '' || isset($seenNormalized[$normalized])) {
                    continue;
                }

                $seenNormalized[$normalized] = true;

                $passesAllCriteria = true;
                foreach (self::CRITERIA_KEYS as $key) {
                    if (empty($idea[$key])) {
                        $passesAllCriteria = false;
                        break;
                    }
                }

                if ($passesAllCriteria) {
                    $accepted[] = $idea;
                    $addedThisRound++;
                }
            }

            // Dừng khi: (a) đã đủ mục tiêu, (b) lượt này không thêm được ý MỚI nào đạt tiêu chí
            // (dry round — tiếp tục gọi thêm nhiều khả năng chỉ tốn tiền không thêm giá trị), hoặc
            // (c) đã chạm số lượt tối đa.
            if (count($accepted) >= $targetCount || $addedThisRound === 0 || $iteration >= $maxIterations) {
                break;
            }

            $currentPrompt = $this->buildFollowUpPrompt($prompt, $accepted, $targetCount - count($accepted));
        }

        if (count($accepted) < $targetCount && $insufficientReason === null) {
            $insufficientReason = sprintf(
                'Đã thử %d lượt sinh ý tưởng, chỉ đạt được %d/%d ý tưởng thoả cả 4 tiêu chí từ dữ liệu nguồn hiện có.',
                $iteration,
                count($accepted),
                $targetCount,
            );
        }

        return [
            'markdown_output' => $this->renderMarkdownTable($accepted, $categoryNote, $audienceAssumption, $insufficientReason),
            'model_used'      => $modelUsed,
            'cost_usd'        => $totalCostUsd,
            'loop_iterations' => $iteration,
        ];
    }

    /**
     * Prompt gốc do client build sẵn (buildLayer2PromptText()) GIỮ NGUYÊN — chỉ NỐI THÊM đoạn yêu
     * cầu bổ sung cho vòng lặp tiếp theo, không cần hiểu cấu trúc TOP/MIDDLE/BOTTOM bên trong.
     * Luôn nối vào PROMPT GỐC (không nối chồng lên $currentPrompt của lượt trước) để độ dài prompt
     * không phình to tích luỹ qua từng lượt — chỉ tăng đúng bằng danh sách ý đã đạt.
     */
    private function buildFollowUpPrompt(string $originalPrompt, array $accepted, int $remaining): string
    {
        $existingList = implode("\n", array_map(
            static fn (array $idea) => '- '.($idea['idea'] ?? ''),
            $accepted,
        ));

        return $originalPrompt."\n\n# Bổ sung — vòng lặp tiếp theo\n"
            ."Bạn đã đề xuất các ý tưởng sau ở (các) lượt trước, ĐÃ đạt cả 4 tiêu chí — GIỮ NGUYÊN, KHÔNG lặp lại trong câu trả lời này:\n"
            .$existingList
            ."\n\nCần thêm ÍT NHẤT {$remaining} ý tưởng MỚI, KHÔNG trùng hoặc gần giống bất kỳ ý nào ở trên (kể cả biến thể chỉ đổi cách diễn đạt nhưng cùng góc khai thác) — vẫn đi qua đủ Bước 1 (đa dạng góc nhìn) và Bước 2 (đánh giá cả 4 tiêu chí + 2 bộ lọc bắt buộc) như đã mô tả ở trên, chỉ trả về những ý MỚI đạt tiêu chí trong câu trả lời này ở trường `ideas`.";
    }

    /**
     * Render bảng Markdown cuối cùng từ dữ liệu có cấu trúc đã gom qua (các) lượt gọi — model
     * KHÔNG còn tự format bảng, chỉ trả field có cấu trúc; PHP đảm bảo format nhất quán bất kể
     * chạy 1 hay nhiều lượt. Cột 4 tiêu chí luôn "Có" vì $accepted chỉ chứa ý ĐÃ lọc đạt cả 4.
     */
    private function renderMarkdownTable(array $accepted, ?string $categoryNote, ?string $audienceAssumption, ?string $insufficientReason): string
    {
        $lines = [];

        if ($categoryNote) {
            $lines[] = $categoryNote;
        }
        if ($audienceAssumption) {
            $lines[] = $audienceAssumption;
        }
        if ($lines !== []) {
            $lines[] = '';
        }

        $lines[] = '| Ý tưởng | Định dạng gợi ý | Khớp trọng tâm? | Góc nhìn độc quyền? | Phục vụ mục tiêu? | Phù hợp đối tượng? | Lý do (1 câu) | Đề xuất tiêu đề video |';
        $lines[] = '| --- | --- | --- | --- | --- | --- | --- | --- |';

        foreach ($accepted as $idea) {
            $lines[] = '| '.implode(' | ', [
                $this->escapeCell($idea['idea'] ?? ''),
                $this->escapeCell($idea['format_suggestion'] ?? ''),
                'Có',
                'Có',
                'Có',
                'Có',
                $this->escapeCell($idea['reason'] ?? ''),
                $this->escapeCell($idea['suggested_title'] ?? ''),
            ]).' |';
        }

        if ($insufficientReason) {
            $lines[] = '';
            $lines[] = $insufficientReason;
        }

        return implode("\n", $lines);
    }

    private function escapeCell(mixed $text): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], trim((string) $text));
    }
}
