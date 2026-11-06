<?php

namespace Modules\Aicem\Features\Generation\Jobs;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Services\AI\Exceptions\AIProviderConfigException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Aicem\Enums\GenerationRunStatus;
use Modules\Aicem\Features\Generation\Actions\BuildOutputSchemaAction;
use Modules\Aicem\Features\Generation\Actions\BuildPromptAction;
use Modules\Aicem\Features\Generation\Actions\CheckAndReserveBudgetAction;
use Modules\Aicem\Features\Generation\Actions\PersistSuggestionsAction;
use Modules\Aicem\Features\Generation\Actions\ReconcileBudgetAction;
use Modules\Aicem\Features\Generation\Actions\ValidateSuggestionsAction;
use Modules\Aicem\Features\Generation\Exceptions\BudgetExceededException;
use Modules\Aicem\Models\AicemGenerationRun;

/**
 * Orchestrate: BuildPromptAction (đã áp trần input) → CheckAndReserveBudgetAction (mục 13.1) →
 * AIProviderManager::complete() → reconcile budget → ValidateSuggestionsAction →
 * PersistSuggestionsAction. Retry duy nhất ở tầng job (mục 8.8) — Provider không tự retry.
 *
 * Nhả reservation (release) CHỈ đặt trong failed() — không lặp lại ở catch bên trong handle() —
 * vì failed() là nơi DUY NHẤT Laravel đảm bảo gọi đúng 1 lần khi job hỏng vĩnh viễn (kể cả sau
 * khi cạn hết $tries lần retry tự nhiên, không chỉ khi gọi $this->fail() thủ công). Gọi release
 * ở cả 2 chỗ sẽ trừ reserved_usd 2 lần cho cùng 1 run.
 */
class RunAicemWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $generationRunId) {}

    /** @return int[] */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(
        BuildPromptAction $buildPrompt,
        BuildOutputSchemaAction $buildSchema,
        CheckAndReserveBudgetAction $checkBudget,
        ReconcileBudgetAction $reconcileBudget,
        AIProviderManager $aiManager,
        ValidateSuggestionsAction $validate,
        PersistSuggestionsAction $persist,
    ): void {
        $run = AicemGenerationRun::find($this->generationRunId);

        if (! $run || $run->status !== GenerationRunStatus::Pending) {
            return;
        }

        $run->update(['status' => GenerationRunStatus::Running, 'started_at' => now()]);

        $workflow     = $run->workflow;
        $subjectClass = config("aicem_subjects.{$run->subject_type}.model");

        if (! $subjectClass) {
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => "subject_type \"{$run->subject_type}\" không có trong registry config/aicem_subjects.php.",
                'completed_at'  => now(),
            ]);

            return;
        }

        $subject = $subjectClass::find($run->subject_id);

        if (! $subject) {
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => "Không tìm thấy {$run->subject_type} #{$run->subject_id} — bài viết/sản phẩm có thể đã bị xoá.",
                'completed_at'  => now(),
            ]);

            return;
        }

        try {
            ['messages' => $messages, 'warnings' => $warnings] = $buildPrompt->handle($workflow, $subject);
            $schema  = $buildSchema->handle($workflow->contextTemplate->schema['output_contract']['item_shape'] ?? []);
            $options = new AIRequestOptions(model: $run->model, responseSchema: $schema);

            // Chỉ reserve 1 LẦN cho cả vòng đời run — nếu job này đang ở lần retry (đã reserve
            // ở lần thử trước do lỗi mạng/429 tạm thời), estimated_cost_usd đã có sẵn, dùng lại
            // chứ không reserve chồng thêm lần nữa (tránh leak ngân sách khi job retry).
            if ($run->estimated_cost_usd === null) {
                $estimated = $checkBudget->handle($run, $messages, $options->maxTokens);
                $run->update(['estimated_cost_usd' => $estimated]);
            }

            $response = $aiManager->complete($messages, $options);

            $rawSuggestions = json_decode($response->content, true)['suggestions'] ?? [];
            $validated      = $validate->handle($run->subject_type, $subject, $rawSuggestions);

            $persist->handle($run, $validated);

            $errorMessage = $warnings ? implode(' | ', $warnings) : null;
            if (empty($validated) && ! empty($rawSuggestions)) {
                $errorMessage = trim(($errorMessage ? $errorMessage . ' | ' : '') . '0 gợi ý hợp lệ sau khi lọc.');
            }

            $run->update([
                'status'                => GenerationRunStatus::Succeeded,
                'input_tokens'          => $response->inputTokens,
                'output_tokens'         => $response->outputTokens,
                'cache_creation_tokens' => $response->cacheCreationInputTokens,
                'cache_read_tokens'     => $response->cacheReadInputTokens,
                'cost_usd'              => $response->costUsd,
                'error_message'         => $errorMessage,
                'completed_at'          => now(),
            ]);

            $reconcileBudget->settle($run->fresh(), $response->costUsd);
        } catch (BudgetExceededException $e) {
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            // Vượt hạn mức không tự khỏi khi retry trong vài giây tới — dừng ngay, không tốn
            // thêm token/thời gian chờ vô ích (mục 8.8, cùng tinh thần AIProviderConfigException).
            $this->fail($e);
        } catch (AIProviderConfigException $e) {
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            $this->fail($e);
        } catch (\Throwable $e) {
            // Bắt mọi lỗi không lường trước (vd $run->workflow null → TypeError ở
            // buildPrompt->handle()) — nếu
            // không có catch-all này, exception thoát ra ngoài handle() mà KHÔNG ghi status
            // Failed ở đây; run có thể kẹt vĩnh viễn ở "running" nếu vì lý do gì đó
            // failed()/retry của Laravel không kịp chạy (worker bị kill giữa chừng, job biến
            // mất khỏi cả jobs lẫn failed_jobs — đã xảy ra thật). Ghi Failed NGAY tại đây
            // (đồng bộ, không phụ thuộc callback failed() của Laravel) rồi mới rethrow để
            // luồng retry/failed_jobs bình thường của Laravel vẫn chạy tiếp nếu có thể.
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => 'Lỗi không mong đợi: ' . $e->getMessage(),
                'completed_at'  => now(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(\Throwable $e): void
    {
        $run = AicemGenerationRun::find($this->generationRunId);

        if (! $run || $run->status === GenerationRunStatus::Succeeded) {
            return;
        }

        // Giữ thông báo cụ thể đã ghi ở catch (VD lỗi API key) nếu có — failed() không nên
        // ghi đè bằng message generic của exception gốc khi ta đã có bản dịch rõ ràng hơn.
        $run->refresh();

        if ($run->status !== GenerationRunStatus::Failed || empty($run->error_message)) {
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => $e->getMessage() ?: 'Job thất bại không rõ nguyên nhân — xem log Laravel để biết chi tiết.',
                'completed_at'  => now(),
            ]);
        }

        app(ReconcileBudgetAction::class)->release($run);
    }
}
