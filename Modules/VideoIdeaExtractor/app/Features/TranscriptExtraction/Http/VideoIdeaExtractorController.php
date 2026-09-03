<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Http;

use App\Http\Controllers\Controller;
use App\Services\AI\Exceptions\AiBudgetExceededException;
use App\Services\AI\Exceptions\AIProviderConfigException;
use App\Services\AI\Exceptions\UnknownModelPricingException;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ContentFoundation\Actions\ListCategoryFoundationsAction;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions\ComputeTranscriptConfidenceAction;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions\ExtractTranscriptAction;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions\RunVideoIdeaAiPromptAction;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions\RunVideoIdeaLayer2Action;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data\ExtractBatchVideoRequestData;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data\ExtractBatchVideoResultData;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data\RawTranscriptData;

class VideoIdeaExtractorController extends Controller
{
    /**
     * Nạp sẵn danh sách chuyên mục + Category Content Foundation qua Js::from() trong view — dùng
     * chung module Modules\ContentFoundation (không có bản sao riêng của module này), cùng cách
     * CoreIdeaExtractorController::index() đã dùng trước khi tách.
     */
    public function index(ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        // withFoundationDetails: false — chỉ trả bản rút gọn (core_focus/unique_angle/rejected_ideas
        // đã cắt) cho MỌI category; full detail của category THẬT SỰ được chọn fetch on-demand qua
        // applyCategoryFoundation() ở index.blade.php (xem docblock ListCategoryFoundationsAction).
        return view('videoideaextractor::index', [
            'categoryFoundations' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    /**
     * Batch tối đa `video_idea_extractor.batch.max_videos` video — KHÔNG có bước fetch/mạng nào
     * (transcript dán tay), nên không có trạng thái "blocked"/"error" như CoreIdeaExtractor —
     * validate() ở đây đã đảm bảo mọi video có đủ title/transcript trước khi vào Layer 1.
     */
    public function extractBatch(Request $request, ExtractTranscriptAction $extractTranscript, ComputeTranscriptConfidenceAction $computeConfidence): JsonResponse
    {
        $maxVideos = (int) config('video_idea_extractor.batch.max_videos', 5);
        $maxChars = (int) config('video_idea_extractor.paste.max_chars', 500_000);

        $data = ExtractBatchVideoRequestData::from($request->validate([
            'videos' => ['required', 'array', 'min:1', "max:{$maxVideos}"],
            'videos.*.title' => ['required', 'string', 'max:255'],
            'videos.*.transcript' => ['required', 'string', "max:{$maxChars}"],
            'topic' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:500'],
            'goal' => ['nullable', 'string', 'max:2000'],
            'constraints' => ['nullable', 'string', 'max:500'],
            'style_sample' => ['nullable', 'string', 'max:3000'],
        ]));

        $maxTranscriptChars = (int) config('video_idea_extractor.batch.max_transcript_chars_per_video', 20000);

        $videos = array_map(function (array $video) use ($extractTranscript, $computeConfidence, $maxTranscriptChars): RawTranscriptData {
            $extracted = $extractTranscript->handle($video['transcript']);
            $confidenceResult = $computeConfidence->handle($extracted['word_count']);
            $truncated = $this->truncateAtBoundary($extracted['transcript'], $maxTranscriptChars);

            return new RawTranscriptData(
                title: $video['title'],
                chapters: $extracted['chapters'],
                transcript: $truncated,
                word_count: $extracted['word_count'],
                extraction_confidence: $confidenceResult['confidence'],
                notes: $this->mergeTruncationNote($confidenceResult['notes'], $extracted['transcript'], $truncated, $maxTranscriptChars),
            );
        }, $data->videos);

        $result = new ExtractBatchVideoResultData(
            topic: $data->topic,
            audience: $data->audience,
            goal: $data->goal,
            constraints: $data->constraints,
            style_sample: $data->style_sample,
            requested_count: count($data->videos),
            videos: $videos,
            processed_at: now()->toIso8601String(),
        );

        return response()->json($result->toApiArray());
    }

    /**
     * Tự động hoá "Layer 2" — CHỈ chạy khi người dùng bấm nút thủ công, cùng khuôn
     * CoreIdeaExtractorController::runLayer2(): `prompt` nhận NGUYÊN VĂN chuỗi client đã build sẵn
     * (buildLayer2PromptText() ở index.blade.php).
     */
    public function runLayer2(Request $request, RunVideoIdeaLayer2Action $action): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:'.config('video_idea_extractor.layer2.max_prompt_chars', 300000)],
        ]);

        $organization = TenantContext::get();

        if (! $organization) {
            return response()->json(['message' => 'Không xác định được tổ chức hiện tại — không thể gọi AI.'], 422);
        }

        try {
            $result = $action->handle($organization, $data['prompt']);
        } catch (AiBudgetExceededException|AIProviderConfigException|UnknownModelPricingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /**
     * "Tiêu đề & Thumbnail" — thao tác trên ĐÚNG 1 video đã trích xuất (khác runLayer2() — sinh ý
     * tưởng từ CẢ batch). Cùng khuôn CoreIdeaExtractorController::summarize()/rewrite(): `prompt`
     * nhận NGUYÊN VĂN chuỗi client đã build sẵn (buildTitlesPromptText() ở index.blade.php).
     */
    public function titles(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'titles', (int) config('video_idea_extractor.titles.max_output_tokens', 800));
    }

    /** "Hook mở đầu" — cùng khuôn titles(), xem docblock ở đó. */
    public function hooks(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'hooks', (int) config('video_idea_extractor.hooks.max_output_tokens', 800));
    }

    /** "Ý tưởng Shorts" — cùng khuôn titles(), xem docblock ở đó. */
    public function shorts(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'shorts', (int) config('video_idea_extractor.shorts.max_output_tokens', 1000));
    }

    /**
     * "Dàn ý thân bài" — cùng khuôn titles() nhưng thuộc nhóm bước SAU khi đã chốt tiêu đề/hook:
     * dựng khung phần thân video sắp quay (không phải chọn phương án như titles/hooks/shorts).
     * Trần bản nháp/độ dài output khai báo riêng ở config, xem 'video_idea_extractor.outline'.
     */
    public function outline(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'outline', (int) config('video_idea_extractor.outline.max_output_tokens', 2000));
    }

    /** "CTA & giữ chân" — cùng khuôn outline(), xem docblock ở đó. */
    public function cta(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'cta', (int) config('video_idea_extractor.cta.max_output_tokens', 1000));
    }

    /**
     * "Biên tập lời nói" — cùng khuôn outline() về mặt vận hành (prompt build sẵn ở client), nhưng
     * là tính năng DUY NHẤT mà output phải chứa lại NGUYÊN VĂN bản nháp đã sửa, nên
     * max_output_tokens cao hơn hẳn — trần bản nháp đầu vào ('polish.max_draft_chars') được kiểm ở
     * client trước khi build prompt, còn ở đây vẫn rơi về trần chung 'layer2.max_prompt_chars'.
     */
    public function polish(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'polish', (int) config('video_idea_extractor.polish.max_output_tokens', 4096));
    }

    /**
     * "Dàn ý Vlog (Hero's Journey)" — cùng khuôn outline() về mặt vận hành, nhưng chất liệu là 1 sự
     * việc THẬT người dùng tự mô tả (không phải transcript) — xem docblock
     * buildVlogOutlinePromptText() ở client cho lý do đầy đủ.
     */
    public function vlogOutline(Request $request, RunVideoIdeaAiPromptAction $action): JsonResponse
    {
        return $this->runPrompt($request, $action, 'vlog_outline', (int) config('video_idea_extractor.vlog_outline.max_output_tokens', 1200));
    }

    private function runPrompt(Request $request, RunVideoIdeaAiPromptAction $action, string $kind, int $maxOutputTokens): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:'.config('video_idea_extractor.layer2.max_prompt_chars', 300000)],
        ]);

        $organization = TenantContext::get();

        if (! $organization) {
            return response()->json(['message' => 'Không xác định được tổ chức hiện tại — không thể gọi AI.'], 422);
        }

        try {
            $result = $action->handle($organization, $data['prompt'], $kind, $maxOutputTokens);
        } catch (AiBudgetExceededException|AIProviderConfigException|UnknownModelPricingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /**
     * Cắt tại ranh giới câu gần nhất — cùng thuật toán truncateAtBoundary() bên
     * CoreIdeaExtractorController (tránh cắt giữa câu/giữa từ, rơi về cắt cứng nếu ranh giới gần
     * nhất quá xa đầu chuỗi).
     */
    private function truncateAtBoundary(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $window = mb_substr($text, 0, $max);
        $minAcceptable = (int) ($max * 0.7);
        $cutAt = null;

        if (preg_match_all('/[.!?](?=\s|$)|\n/u', $window, $matches, PREG_OFFSET_CAPTURE)) {
            [$boundary, $byteOffset] = end($matches[0]);
            $charOffset = mb_strlen(substr($window, 0, $byteOffset));
            $cutAt = $boundary === "\n" ? $charOffset : $charOffset + 1;
        }

        if ($cutAt !== null && $cutAt >= $minAcceptable) {
            return rtrim(mb_substr($text, 0, $cutAt)).'…';
        }

        return $window.'…';
    }

    /**
     * `word_count`/`extraction_confidence` (ComputeTranscriptConfidenceAction) được tính trên
     * transcript ĐÃ LÀM SẠCH nhưng CHƯA CẮT — trong khi `transcript` trả về (và mọi prompt AI ở
     * runPrompt()/runLayer2() dùng làm CHẤT LIỆU DUY NHẤT) là bản ĐÃ CẮT theo
     * `max_transcript_chars_per_video`. Video dài (60-90 phút thực tế thường vượt xa trần 20.000 ký
     * tự/video) khiến 2 con số này lệch nhau: badge/word_count vẫn báo "Cao" (dựa trên TOÀN BỘ
     * transcript dán vào), nhưng Tiêu đề/Hook/Ý tưởng/Kịch bản sinh ra chỉ dựa trên phần ĐẦU video
     * — biên tập viên không có cách nào biết phần sau bị bỏ qua nếu không có ghi chú riêng này.
     */
    private function mergeTruncationNote(?string $confidenceNote, string $fullTranscript, string $truncatedTranscript, int $maxChars): ?string
    {
        $originalLength = mb_strlen($fullTranscript);

        if ($originalLength <= $maxChars) {
            return $confidenceNote;
        }

        $percentKept = (int) round(mb_strlen($truncatedTranscript) / $originalLength * 100);
        $truncationNote = sprintf(
            'Transcript gốc dài %s ký tự, đã CẮT BỚT còn ~%d%% (trần %s ký tự/video) trước khi đưa vào AI — mọi Tiêu đề/Hook/Ý tưởng/Kịch bản bên dưới chỉ dựa trên phần ĐẦU video, CHƯA phản ánh nội dung phần sau. Cân nhắc tách video này thành nhiều lượt trích xuất theo từng đoạn nếu cần khai thác trọn vẹn.',
            number_format($originalLength),
            $percentKept,
            number_format($maxChars),
        );

        return $confidenceNote ? "{$confidenceNote} {$truncationNote}" : $truncationNote;
    }
}
