<?php

namespace Modules\AiCopilot\Actions;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AiCopilot\Models\AiMonthlyUsage;
use Modules\AiCopilot\Models\AiRequest;
use Modules\Subscription\Features\FeatureGate\Actions\RecordFeatureUsageAction;

class RecordAiUsageAction
{
    use AsAction;

    public function handle(AiRequest $aiRequest): void
    {
        $yearMonth = now()->format('Y-m');
        $orgId     = $aiRequest->organization_id;
        $agentId   = $aiRequest->agent_id;

        // Deduct từ subscription quota (nguồn truth)
        $org = TenantContext::get() ?? Organization::find($orgId);
        if ($org) {
            RecordFeatureUsageAction::run($org, 'quota.ai_requests', 1);
            RecordFeatureUsageAction::run($org, 'quota.ai_tokens', max(1, (int) $aiRequest->total_tokens));
        }

        // Cập nhật monthly aggregate (cost/token dashboard)
        // withoutTenant(): $orgId đã biết chắc từ $aiRequest — bypass global scope để tránh
        // double-filter và an toàn khi chạy trong job context.
        DB::transaction(function () use ($orgId, $agentId, $aiRequest, $yearMonth) {
            // Org-level total
            $total = AiMonthlyUsage::withoutTenant()->lockForUpdate()->firstOrCreate(
                ['organization_id' => $orgId, 'year_month' => $yearMonth, 'agent_id' => null],
                ['task_type' => null]
            );
            $total->increment('total_requests');
            $total->increment('successful_requests');
            $total->increment('total_input_tokens', (int) $aiRequest->input_tokens);
            $total->increment('total_output_tokens', (int) $aiRequest->output_tokens);
            $total->increment('total_tokens', (int) $aiRequest->total_tokens);
            $total->increment('total_cost_usd', (float) $aiRequest->cost_usd);

            // Per-agent breakdown
            $byAgent = AiMonthlyUsage::withoutTenant()->lockForUpdate()->firstOrCreate(
                ['organization_id' => $orgId, 'year_month' => $yearMonth, 'agent_id' => $agentId],
                ['task_type' => $aiRequest->agent?->task_type]
            );
            $byAgent->increment('total_requests');
            $byAgent->increment('successful_requests');
            $byAgent->increment('total_input_tokens', (int) $aiRequest->input_tokens);
            $byAgent->increment('total_output_tokens', (int) $aiRequest->output_tokens);
            $byAgent->increment('total_tokens', (int) $aiRequest->total_tokens);
            $byAgent->increment('total_cost_usd', (float) $aiRequest->cost_usd);
        });
    }
}
