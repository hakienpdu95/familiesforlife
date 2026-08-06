<?php

namespace Modules\N8n\Features\Maintenance\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\N8n\Models\N8nInboundLog;
use Modules\N8n\Models\N8nOutboundLog;

/**
 * spec/N8n_Integration_Technical_Specification.md §5.7 — xoá n8n_inbound_logs/n8n_outbound_logs
 * cũ hơn config('n8n.log_retain_days', 30). Đăng ký trong routes/console.php bằng
 * Schedule::call() — cùng pattern WorkflowAutomation\Actions\PurgeOldExecutionsAction.
 */
class PurgeOldN8nLogsAction
{
    use AsAction;

    public function handle(): void
    {
        $cutoff = now()->subDays((int) config('n8n.log_retain_days', 30));

        N8nInboundLog::where('received_at', '<', $cutoff)->delete();
        N8nOutboundLog::where('requested_at', '<', $cutoff)->delete();
    }
}
