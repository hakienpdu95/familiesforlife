<?php

namespace Modules\WorkflowAutomation\Core;

use Illuminate\Contracts\Cache\Repository;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Enums\CooldownType;
use Modules\WorkflowAutomation\Models\Workflow;

final class CooldownGuard
{
    public function __construct(
        private readonly Repository $cache,
    ) {}

    public function allow(Workflow $workflow, TriggerPayload $payload): bool
    {
        $type = CooldownType::from($workflow->cooldown_type);
        if ($type === CooldownType::None) return true;

        $key = $this->key($type, $workflow, $payload);

        if ($this->cache->has($key)) return false;

        $this->cache->put($key, 1, $type->ttlSeconds());
        return true;
    }

    private function key(CooldownType $type, Workflow $workflow, TriggerPayload $payload): string
    {
        $wid = $workflow->id;
        $sid = $payload->subjectId ?? 'null';
        $oid = $payload->organizationId ?? 'null';

        return match ($type) {
            CooldownType::OncePerSubject    => "wf:cd:once:{$oid}:{$wid}:{$sid}",
            CooldownType::PerSubjectPerDay  => "wf:cd:day:{$oid}:{$wid}:{$sid}:" . now()->format('Ymd'),
            CooldownType::PerSubjectPerHour => "wf:cd:hr:{$oid}:{$wid}:{$sid}:" . now()->format('YmdH'),
            CooldownType::GlobalPerDay      => "wf:cd:gday:{$oid}:{$wid}:" . now()->format('Ymd'),
            default                         => "wf:cd:none:{$wid}",
        };
    }
}
