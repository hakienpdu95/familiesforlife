<?php

namespace Modules\Aicem\Features\Generation\Jobs;

use App\Foundation\Jobs\TenantAwareJob;
use App\Services\AI\AIProviderManager;
use App\Services\AI\AIRequestOptions;
use App\Services\AI\Exceptions\AIProviderConfigException;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
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
 * KHÔNG dùng $this->withTenant() kế thừa từ TenantAwareJob (dựa vào TenantContext tại thời điểm
 * DISPATCH) — vì super-admin (organization_id=NULL, bypass OrganizationScope hoàn toàn) có thể
 * dispatch job này trong khi đang thao tác trên 1 subject thuộc Organization KHÁC với TenantContext
 * ambient của họ lúc đó. Organization đúng DUY NHẤT là `$run->organization_id` — đã được
 * StartGenerationRunAction gán chính xác từ $subject->organization_id ngay lúc tạo, không phụ
 * thuộc ai là người bấm. Job tự đọc lại giá trị này và tự set TenantContext, không tin
 * $this->organizationId (ambient, có thể sai).
 *
 * Nhả reservation (release) CHỈ đặt trong failed() — không lặp lại ở catch bên trong handle() —
 * vì failed() là nơi DUY NHẤT Laravel đảm bảo gọi đúng 1 lần khi job hỏng vĩnh viễn (kể cả sau
 * khi cạn hết $tries lần retry tự nhiên, không chỉ khi gọi $this->fail() thủ công). Gọi release
 * ở cả 2 chỗ sẽ trừ reserved_usd 2 lần cho cùng 1 run.
 */
class RunAicemWorkflowJob extends TenantAwareJob
{
    public int $tries = 3;

    public function __construct(public readonly int $generationRunId)
    {
        parent::__construct();
    }

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
        // withoutGlobalScopes() — chưa có TenantContext nào được set ở bước này, phải đọc được
        // hàng run bất kể tenant hiện tại là gì để biết ĐÚNG Organization cần chuyển sang.
        $run = AicemGenerationRun::withoutGlobalScopes()->find($this->generationRunId);

        if (! $run || $run->status !== GenerationRunStatus::Pending) {
            return;
        }

        $organization = Organization::find($run->organization_id);

        if (! $organization) {
            $run->update([
                'status'        => GenerationRunStatus::Failed,
                'error_message' => "Organization #{$run->organization_id} không còn tồn tại — không thể xử lý run này.",
                'completed_at'  => now(),
            ]);

            return;
        }

        TenantContext::runForOrganization($organization, function () use ($run, $buildPrompt, $buildSchema, $checkBudget, $reconcileBudget, $aiManager, $validate, $persist) {
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
                    'error_message' => "Không tìm thấy {$run->subject_type} #{$run->subject_id} trong Organization "
                        . "#{$run->organization_id} ({$run->organization->name}) — bài viết/sản phẩm có thể đã bị xoá.",
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

                $response = $aiManager->complete(TenantContext::resolve(), $messages, $options);

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
            }
        });
    }

    public function failed(\Throwable $e): void
    {
        $run = AicemGenerationRun::withoutGlobalScopes()->find($this->generationRunId);

        if (! $run || $run->status === GenerationRunStatus::Succeeded) {
            return;
        }

        $organization = Organization::find($run->organization_id);

        $apply = function () use ($run, $e) {
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
        };

        $organization ? TenantContext::runForOrganization($organization, $apply) : $apply();
    }
}
