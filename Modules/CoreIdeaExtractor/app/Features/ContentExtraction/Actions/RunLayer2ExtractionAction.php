<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\AiBudgetExceededException;

/**
 * Tự động hoá "Layer 2" (spec/CoreIdeaExtractor.md §6/§12.3) — CHỈ chạy khi người dùng bấm nút
 * "Chạy Layer 2 bằng AI" thủ công (KHÔNG tự động sau Layer 1), theo đúng yêu cầu 2026-07-28: cần
 * kiểm soát chi phí + để người dùng tối ưu nội dung kỹ thuật (chỉnh sửa Layer 1/ngữ cảnh chuyên
 * mục) trước khi thực sự tốn tiền gọi AI.
 *
 * QUAN TRỌNG: tái dùng NGUYÊN VĂN prompt đã được tinh chỉnh qua nhiều phiên bản ở
 * copyPromptForAi() (JS, index.blade.php — TOP/MIDDLE/BOTTOM: persona + Category Content
 * Foundation + JSON Layer 1 đầy đủ + BƯỚC 0/1/2/3) — $prompt nhận NGUYÊN VĂN chuỗi client đã build
 * sẵn, KHÔNG build lại logic prompt ở PHP (tránh trùng lặp + lệch pha giữa 2 nơi).
 *
 * 2026-08 — GOAL-BASED LOOP THẬT (tham khảo khái niệm "loop" Anthropic công bố: Goal → Work →
 * Check → Improve → Repeat), thay cho bản cũ "1 lần gọi AI, tin model tự lặp lại Bước 1 trong đầu
 * nếu chưa đủ ý tưởng" — bản cũ không có cách nào PHP biết model có thực sự tự lặp hay không, chỉ
 * đọc được đúng những gì model TRẢ VỀ trong 1 response duy nhất. Giờ PHP tự ĐẾM số ý tưởng đạt cả
 * 4 tiêu chí bằng field boolean tường minh sau mỗi lần gọi, và tự gọi AI THÊM nếu chưa đủ
 * `layer2.target_idea_count`, tối đa `layer2.max_loop_iterations` lần — mỗi lần nhắc lại các ý ĐÃ
 * đạt để model không lặp. Ranh giới client/server GIỮ NGUYÊN — action này chỉ thêm phần ĐIỀU KHIỂN
 * VÒNG LẶP + RENDER bảng Markdown cuối cùng từ dữ liệu có cấu trúc (model không còn tự format
 * bảng). Cùng thiết kế schema với RunVideoIdeaLayer2Action bên VideoIdeaExtractor, có thêm field
 * `category` per-idea (chỉ dùng ở chế độ CHƯA chọn chuyên mục — BƯỚC 0 nhánh "chưa chọn").
 *
 * 2026-08-05 — PROMPT CACHING cho vòng lặp (gap-analysis đối chiếu leewayhertz.com/prompt-
 * engineering + spec §12 changelog): hạ tầng `AIRequestOptions`/`AnthropicProvider` đã hỗ trợ
 * `role=system` + `cacheable=true` (dùng bởi Modules/Aicem/BuildPromptAction) nhưng module này
 * trước đó CHỈ gửi 1 message `role=user` duy nhất — mỗi lượt lặp gửi lại NGUYÊN VĂN $prompt gốc
 * (persona + Category Content Foundation + JSON Layer 1, có thể hàng chục nghìn ký tự) dù nội
 * dung đó KHÔNG đổi giữa các lượt trong cùng 1 lần chạy. Giờ tách: $prompt gốc → 1 message
 * `system` cacheable=true (gửi y hệt mọi lượt, Anthropic tính phí cache-read rẻ hơn nhiều từ lượt
 * 2 trở đi); phần đổi mỗi lượt (đoạn "Bổ sung — vòng lặp tiếp theo" hoặc lời mở đầu lượt 1) →
 * message `user`. KHÔNG đổi nội dung/ý nghĩa prompt — chỉ đổi vai trò message + cách nối chuỗi
 * (buildFollowUpPrompt() không còn nối vào $originalPrompt, chỉ trả đúng đoạn bổ sung). OpenAI bỏ
 * qua cờ `cacheable` an toàn (xem OpenAIProvider::complete()) nên không ảnh hưởng tenant dùng
 * OpenAI, chỉ đơn giản không được hưởng caching (đúng hành vi cũ).
 */
class RunLayer2ExtractionAction
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
                        'idea' => ['type' => 'string', 'description' => 'Tên/nội dung ý tưởng bài viết, đủ cụ thể để hiểu góc khai thác.'],
                        'category' => ['type' => ['string', 'null'], 'description' => 'CHỈ khi Bước 0 áp dụng nhánh "chưa chọn chuyên mục": tên chuyên mục đề xuất cho ĐÚNG ý tưởng này (copy đúng từ "Danh sách chuyên mục"). Null nếu đã chọn 1 chuyên mục từ trước.'],
                        'matches_core_focus' => ['type' => 'boolean', 'description' => 'Tiêu chí 1 (Bước 2) — khớp trọng tâm nội dung chuyên mục.'],
                        'unique_angle' => ['type' => 'boolean', 'description' => 'Tiêu chí 2 (Bước 2) — thể hiện góc nhìn độc quyền của chuyên mục.'],
                        'serves_goal' => ['type' => 'boolean', 'description' => 'Tiêu chí 3 (Bước 2) — phục vụ mục tiêu bài viết đã nêu.'],
                        'fits_audience' => ['type' => 'boolean', 'description' => 'Tiêu chí 4 (Bước 2) — phù hợp đối tượng độc giả đã nêu.'],
                        'reason' => ['type' => 'string', 'description' => 'Lý do ngắn (1 câu) vì sao ý tưởng đạt cả 4 tiêu chí.'],
                        'suggested_title' => ['type' => 'string', 'description' => 'Đề xuất tiêu đề bài viết cho ý tưởng này.'],
                        // 2026-08-05 — explainability/auditability (gap đối chiếu tigergraph.com/
                        // blog/context-engineering-vs-prompt-engineering, spec §12 changelog): mỗi
                        // nguồn trong payload MIDDLE đã có `title`/`url` định danh rõ, nhưng trước
                        // đây model không bị bắt buộc trích dẫn đã dùng nguồn nào cho từng ý tưởng
                        // — biên tập viên không tự kiểm chứng lại được khi chạy batch nhiều nguồn.
                        'source_reference' => ['type' => 'string', 'description' => 'Nguồn đã dùng làm căn cứ chính cho ý tưởng này — copy đúng `title` (và `url` nếu có) của (các) nguồn tương ứng trong "Dữ liệu nguồn"; tổng hợp từ nhiều nguồn thì liệt kê cách nhau bằng dấu chấm phẩy.'],
                    ],
                    'required' => ['idea', 'category', 'matches_core_focus', 'unique_angle', 'serves_goal', 'fits_audience', 'reason', 'suggested_title', 'source_reference'],
                ],
            ],
            // 2026-08-04 — thay field `suggested_product` (1 chuỗi nullable trên TỪNG ý tưởng) bằng
            // danh sách RIÊNG cấp kết quả, tối thiểu 5 mục cho CẢ BỘ ý tưởng (yêu cầu người dùng —
            // xem khối "Gợi ý sản phẩm" ở cuối buildLayer2PromptText()). Lý do đổi: bản per-idea xét
            // từng ý độc lập nên phần lớn trả null, thực tế cho ra 0-2 sản phẩm/lần chạy; và không
            // diễn đạt được trường hợp 1 sản phẩm phục vụ nhiều ý tưởng cùng lúc.
            'suggested_products' => [
                'type' => 'array',
                'description' => 'TỐI THIỂU 5 loại sản phẩm/dịch vụ gắn được TỰ NHIÊN vào (các) ý tưởng ở `ideas` — xem đầy đủ nguyên tắc chọn + 5 ràng buộc ở khối "Gợi ý sản phẩm" cuối prompt. Trả ÍT hơn 5 (hoặc mảng rỗng) khi thực sự không có sản phẩm nào phù hợp tự nhiên, KHÔNG bịa cho đủ số.',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'product' => ['type' => 'string', 'description' => 'Tên LOẠI sản phẩm/dịch vụ (VD "ghế ăn dặm có đai an toàn") — không nêu tên thương hiệu, không nêu giá.'],
                        'why_easy_to_explain' => ['type' => 'string', 'description' => 'Vì sao người sáng tạo nội dung giải thích được sản phẩm này trong 3 giây (1 câu ngắn).'],
                        'for_ideas' => ['type' => 'string', 'description' => 'Tên (các) ý tưởng ở `ideas` dùng được sản phẩm này, copy đúng text `idea`; nhiều ý thì ngăn cách bằng dấu chấm phẩy.'],
                    ],
                    'required' => ['product', 'why_easy_to_explain', 'for_ideas'],
                ],
            ],
            'category_note' => [
                'type' => ['string', 'null'],
                'description' => 'CHỈ khi Bước 0 áp dụng (lệch chủ đề với chuyên mục đã chọn, HOẶC chưa chọn chuyên mục): đúng 1 câu kết luận Bước 0 (VD "Lưu ý: nguồn phù hợp hơn với chuyên mục \'...\'", "Chuyên mục phù hợp nhất: [tên]", "Nguồn đa chuyên mục — phân bổ theo: ...", hoặc "chưa xác định được"). Null nếu Bước 0 không áp dụng (khớp chuyên mục đã chọn, không cần ghi chú).',
            ],
            'audience_assumption' => [
                'type' => ['string', 'null'],
                'description' => 'CHỈ khi chưa có mô tả đối tượng độc giả (tiêu chí 4, Bước 2): 1 dòng "Giả định đối tượng: [mô tả]" (đã chọn chuyên mục), hoặc tối đa 3 dòng "Giả định đối tượng — [chuyên mục]: [mô tả]" nối bằng xuống dòng (chưa chọn chuyên mục, mỗi chuyên mục 1 dòng). Null nếu đã có mô tả đối tượng.',
            ],
            'insufficient_reason' => [
                'type' => ['string', 'null'],
                'description' => 'CHỈ khi đã cố hết sức nhưng KHÔNG thể tạo thêm ý tưởng mới đạt tiêu chí trong lượt này (đã khai thác hết góc nhìn hợp lý, hoặc do lệch chủ đề/không xác định được chuyên mục phù hợp ở Bước 0): 1 câu lý do ngắn. Null nếu vẫn còn góc nhìn để khai thác.',
            ],
        ],
        'required' => ['ideas', 'suggested_products'],
    ];

    /** Sàn số lượng sản phẩm gợi ý — khớp con số nêu trong prompt (buildLayer2PromptText). */
    private const MIN_SUGGESTED_PRODUCTS = 5;

    private const CRITERIA_KEYS = ['matches_core_focus', 'unique_angle', 'serves_goal', 'fits_audience'];

    /**
     * Lượt gọi ĐẦU TIÊN không có "Bổ sung — vòng lặp tiếp theo" để làm user turn (chưa có ý nào
     * đã đạt) — cần 1 câu mở đầu ngắn để đóng vai trò user turn bắt buộc của API, toàn bộ nội
     * dung thật (persona/nhiệm vụ/BƯỚC 0-3) đã nằm trong system message rồi.
     */
    private const KICKOFF_MESSAGE = 'Thực hiện đúng theo hướng dẫn ở trên, bắt đầu từ BƯỚC 0.';

    public function __construct(
        private readonly AIProviderManager $aiProviderManager,
        private readonly CheckCoreIdeaAiBudgetAction $budget,
    ) {}

    /** @return array{markdown_output: string, model_used: string, cost_usd: float, loop_iterations: int} */
    public function handle(Organization $organization, string $prompt): array
    {
        $config = $organization->ai_provider_config ?? config('ai.default');
        $provider = $config['provider'] ?? 'anthropic';
        $model = $config['model'] ?? config('ai.default.model');

        $maxOutputTokens = (int) config('core_idea_extractor.layer2.max_output_tokens', 4096);
        $targetCount = (int) config('core_idea_extractor.layer2.target_idea_count', 10);
        $maxIterations = max(1, (int) config('core_idea_extractor.layer2.max_loop_iterations', 3));

        // 2026-08 — nâng từ 0.3 lên 0.7: BƯỚC 1 của prompt yêu cầu brainstorm RỘNG 20-25 ý tưởng
        // đa dạng góc nhìn — nhiệt độ thấp (0.3) dễ ra nhiều ý tưởng chỉ khác câu chữ nhưng cùng
        // góc khai thác, đi ngược đúng mục tiêu "đa dạng" mà prompt đang yêu cầu. Không đẩy cao hơn
        // (VD 0.9-1.0) vì mỗi ý tưởng vẫn phải bám ĐÚNG dữ liệu nguồn thật, 0.7 là điểm cân bằng.
        $options = new AIRequestOptions(
            model: $model,
            responseSchema: self::RESPONSE_SCHEMA,
            temperature: 0.7,
            maxTokens: $maxOutputTokens,
        );

        $accepted = [];
        $seenNormalized = [];
        $products = [];
        $seenProducts = [];
        $totalCostUsd = 0.0;
        $modelUsed = $model;
        $categoryNote = null;
        $audienceAssumption = null;
        $insufficientReason = null;
        // Lượt 1: chưa có gì để nối thêm → dùng KICKOFF_MESSAGE làm user turn. Từ lượt 2: chỉ
        // đúng đoạn "Bổ sung" (không còn nối chồng $prompt gốc) — $prompt gốc đã tách sang system
        // message cacheable, gửi y hệt mọi lượt để Anthropic tính phí cache-read.
        $userTurn = self::KICKOFF_MESSAGE;
        $iteration = 0;

        // Gọi AI đồng bộ trong request (nút bấm thủ công, không phải job nền) — nới execution
        // time limit rộng hơn bản 1-lần-gọi cũ vì giờ có thể chạy tới $maxIterations lượt liên
        // tiếp trong CÙNG 1 request.
        set_time_limit(180);

        while ($iteration < $maxIterations) {
            $iteration++;

            // Ước lượng thô cho budget pre-check (chưa biết cache-read rẻ hơn cache-write tới đâu
            // — dùng tổng độ dài system+user làm cận trên an toàn, giống hành vi ước lượng cũ).
            $estimatedInputTokens = (int) ceil((mb_strlen($prompt) + mb_strlen($userTurn)) / 4);

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
                ['role' => 'system', 'content' => $prompt, 'cacheable' => true],
                ['role' => 'user', 'content' => $userTurn],
            ], $options);

            $this->budget->recordActualCost($organization, $response->costUsd);
            $totalCostUsd += $response->costUsd;
            $modelUsed = $response->modelUsed;

            // Log riêng cho dashboard Aicem ("Tổng quan") — 1 lần bấm "Chạy Layer 2" giờ có thể
            // tương ứng NHIỀU dòng nếu vòng lặp chạy >1 lượt (mỗi lượt gọi AI thật ghi 1 dòng).
            DB::table('cie_layer2_runs')->insert([
                'organization_id' => $organization->id,
                'cost_usd' => $response->costUsd,
                'model_used' => $response->modelUsed,
                'created_at' => now(),
            ]);

            $decoded = json_decode($response->content, associative: true);
            $ideas = is_array($decoded['ideas'] ?? null) ? $decoded['ideas'] : [];

            if (! empty($decoded['category_note'])) {
                $categoryNote = (string) $decoded['category_note'];
            }
            if (! empty($decoded['audience_assumption'])) {
                $audienceAssumption = (string) $decoded['audience_assumption'];
            }
            if (! empty($decoded['insufficient_reason'])) {
                $insufficientReason = (string) $decoded['insufficient_reason'];
            }

            // Gom sản phẩm qua MỌI lượt (không chỉ lượt 1): mỗi lượt sinh thêm ý tưởng mới thì cũng
            // có thể mở ra loại sản phẩm mới. Dedup theo tên đã chuẩn hoá để 1 sản phẩm model lặp
            // lại ở lượt sau không thành 2 dòng — buildFollowUpPrompt() đã liệt kê sản phẩm đã có
            // để model tự tránh, đây là lưới phụ.
            foreach ((is_array($decoded['suggested_products'] ?? null) ? $decoded['suggested_products'] : []) as $product) {
                if (! is_array($product)) {
                    continue;
                }

                $name = trim((string) ($product['product'] ?? ''));
                $key = mb_strtolower($name);

                if ($name === '' || isset($seenProducts[$key])) {
                    continue;
                }

                $seenProducts[$key] = true;
                $products[] = $product;
            }

            $addedThisRound = 0;

            foreach ($ideas as $idea) {
                if (! is_array($idea)) {
                    continue;
                }

                $ideaText = trim((string) ($idea['idea'] ?? ''));
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
            // (dry round), hoặc (c) đã chạm số lượt tối đa.
            if (count($accepted) >= $targetCount || $addedThisRound === 0 || $iteration >= $maxIterations) {
                break;
            }

            $userTurn = $this->buildFollowUpPrompt($accepted, $targetCount - count($accepted), $products);
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
            'markdown_output' => $this->renderMarkdownTable($accepted, $products, $categoryNote, $audienceAssumption, $insufficientReason),
            'model_used' => $modelUsed,
            'cost_usd' => $totalCostUsd,
            'loop_iterations' => $iteration,
        ];
    }

    /**
     * Đoạn "Bổ sung" cho vòng lặp tiếp theo — KHÔNG còn nối vào $prompt gốc (2026-08-05, xem
     * docblock class): $prompt gốc đã là system message cacheable riêng, gửi y hệt mọi lượt; hàm
     * này chỉ trả đúng phần THAY ĐỔI theo lượt (danh sách ý đã đạt + sản phẩm đã có) để làm user
     * turn, không cần hiểu cấu trúc TOP/MIDDLE/BOTTOM của prompt gốc.
     */
    private function buildFollowUpPrompt(array $accepted, int $remaining, array $products): string
    {
        $existingList = implode("\n", array_map(
            static function (array $idea): string {
                $category = $idea['category'] ?? null;

                return $category ? "- {$idea['idea']} (chuyên mục: {$category})" : "- {$idea['idea']}";
            },
            $accepted,
        ));

        // `suggested_products` nằm trong `required` của schema nên lượt nào model cũng phải trả
        // trường này — nói rõ đã gom được gì để nó chỉ bổ sung sản phẩm MỚI (hoặc trả mảng rỗng khi
        // đã đủ), thay vì lặp lại 5 dòng cũ mỗi lượt cho tốn token rồi bị dedup vứt đi.
        $productNote = $products === []
            ? "\n\nVề `suggested_products`: chưa gom được sản phẩm nào ở (các) lượt trước — lượt này cần đề xuất tối thiểu ".self::MIN_SUGGESTED_PRODUCTS.' sản phẩm theo đúng ràng buộc đã nêu ở cuối prompt.'
            : "\n\nVề `suggested_products`, đã có sẵn các sản phẩm sau — KHÔNG lặp lại:\n"
                .implode("\n", array_map(static fn (array $p): string => '- '.($p['product'] ?? ''), $products))
                ."\n(Tổng đang có ".count($products).'/'.self::MIN_SUGGESTED_PRODUCTS.' tối thiểu.) Chỉ bổ sung sản phẩm MỚI khác loại nếu các ý tưởng mới ở lượt này mở ra loại sản phẩm chưa có; nếu không có gì mới, trả về mảng rỗng.';

        return "# Bổ sung — vòng lặp tiếp theo\n"
            ."Bạn đã đề xuất các ý tưởng sau ở (các) lượt trước, ĐÃ đạt cả 4 tiêu chí — GIỮ NGUYÊN, KHÔNG lặp lại trong câu trả lời này:\n"
            .$existingList
            ."\n\nCần thêm ÍT NHẤT {$remaining} ý tưởng MỚI, KHÔNG trùng hoặc gần giống bất kỳ ý nào ở trên (kể cả biến thể chỉ đổi cách diễn đạt nhưng cùng góc khai thác) — vẫn đi qua đủ Bước 1 (đa dạng góc nhìn) và Bước 2 (đánh giá cả 4 tiêu chí + 2 bộ lọc bắt buộc) như đã mô tả ở trên, chỉ trả về những ý MỚI đạt tiêu chí trong câu trả lời này ở trường `ideas`."
            .$productNote;
    }

    /**
     * Render bảng Markdown cuối cùng từ dữ liệu có cấu trúc đã gom qua (các) lượt gọi — model
     * KHÔNG còn tự format bảng, chỉ trả field có cấu trúc; PHP đảm bảo format nhất quán bất kể
     * chạy 1 hay nhiều lượt. Cột "Chuyên mục đề xuất" chỉ xuất hiện nếu CÓ ít nhất 1 ý mang field
     * `category` (chế độ chưa chọn chuyên mục) — khớp đúng 2 biến thể cột mà BƯỚC 3 của prompt mô
     * tả (có/không chuyên mục đã chọn), không cần Action biết trước đang ở chế độ nào.
     */
    private function renderMarkdownTable(array $accepted, array $products, ?string $categoryNote, ?string $audienceAssumption, ?string $insufficientReason): string
    {
        $hasCategoryColumn = false;
        foreach ($accepted as $idea) {
            if (! empty($idea['category'])) {
                $hasCategoryColumn = true;
                break;
            }
        }

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

        $header = ['Ý tưởng'];
        if ($hasCategoryColumn) {
            $header[] = 'Chuyên mục đề xuất';
        }
        $header = array_merge($header, ['Khớp trọng tâm?', 'Góc nhìn độc quyền?', 'Phục vụ mục tiêu?', 'Phù hợp đối tượng?', 'Lý do (1 câu, vì sao đạt cả 4)', 'Đề xuất tiêu đề bài viết', 'Nguồn căn cứ']);

        // Heading cho bảng 1: từ 2026-08-04 output có thể gồm 2 bảng (ý tưởng + sản phẩm), không
        // còn 1 bảng trần như trước — đặt tên đúng bố cục mà nhánh `forExternalChat` của
        // buildLayer2PromptText() yêu cầu chat AI ngoài trả về, để 2 đường đi nhìn giống nhau.
        $lines[] = '## Ý tưởng';
        $lines[] = '| '.implode(' | ', $header).' |';
        $lines[] = '| '.implode(' | ', array_fill(0, count($header), '---')).' |';

        foreach ($accepted as $idea) {
            $cells = [$this->escapeCell($idea['idea'] ?? '')];

            if ($hasCategoryColumn) {
                $cells[] = $this->escapeCell($idea['category'] ?? '');
            }

            $cells[] = 'Có';
            $cells[] = 'Có';
            $cells[] = 'Có';
            $cells[] = 'Có';
            $cells[] = $this->escapeCell($idea['reason'] ?? '');
            $cells[] = $this->escapeCell($idea['suggested_title'] ?? '');
            $cells[] = $this->escapeCell($idea['source_reference'] ?? '');

            $lines[] = '| '.implode(' | ', $cells).' |';
        }

        if ($insufficientReason) {
            $lines[] = '';
            $lines[] = $insufficientReason;
        }

        return implode("\n", array_merge($lines, $this->renderProductLines($products)));
    }

    /**
     * Bảng 2 — sản phẩm gợi ý cho CẢ BỘ ý tưởng (không còn là 1 cột của bảng ý tưởng như trước
     * 2026-08-04). Bỏ hẳn cả khối khi model không đề xuất được sản phẩm nào — trang chủ đề thuần
     * kiến thức/tâm lý hoàn toàn có thể không gắn với đồ dùng nào, prompt đã cho phép trả ít hơn
     * sàn kèm lý do thay vì bịa. Khi có nhưng CHƯA đủ sàn thì thêm 1 dòng nhắc để người biên tập
     * biết đây là giới hạn của chất liệu nguồn, không phải lỗi hiển thị.
     *
     * @return string[]
     */
    private function renderProductLines(array $products): array
    {
        if ($products === []) {
            return [];
        }

        $lines = ['', '## Sản phẩm gợi ý cho cả bộ ý tưởng'];
        $lines[] = '| # | Sản phẩm/dịch vụ | Vì sao dễ giải thích trong 3 giây | Dùng cho ý tưởng nào |';
        $lines[] = '| --- | --- | --- | --- |';

        foreach (array_values($products) as $index => $product) {
            $lines[] = '| '.($index + 1)
                .' | '.$this->escapeCell($product['product'] ?? '')
                .' | '.$this->escapeCell($product['why_easy_to_explain'] ?? '')
                .' | '.$this->escapeCell($product['for_ideas'] ?? '').' |';
        }

        if (count($products) < self::MIN_SUGGESTED_PRODUCTS) {
            $lines[] = '';
            $lines[] = sprintf(
                'Chỉ gợi ý được %d/%d sản phẩm — dữ liệu nguồn không có thêm loại sản phẩm nào gắn tự nhiên với các ý tưởng trên.',
                count($products),
                self::MIN_SUGGESTED_PRODUCTS,
            );
        }

        return $lines;
    }

    private function escapeCell(mixed $text): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], trim((string) $text));
    }
}
