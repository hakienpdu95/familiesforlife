<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Models\ActivityLog;
use Rap2hpoutre\FastExcel\FastExcel;

class ExportActivityLogsAction
{
    use AsAction;

    public string $jobQueue   = 'actlog';
    public int    $jobTimeout = 300;

    public const ALLOWED_FILTERS = [
        'module', 'action', 'level_min', 'actor_id',
        'date_from', 'date_to', 'search', 'organization_id',
    ];

    public function handle(array $filters, string $exportKey): void
    {
        $path = storage_path("app/exports/actlog_{$exportKey}.xlsx");

        $collection = ActivityLog::with(['http'])
            ->tap(fn ($q) => $this->applyFilters($q, $filters))
            ->orderByDesc('created_at')
            ->lazy(500)
            ->map(fn (ActivityLog $log) => [
                'ID'          => $log->id,
                'Thời gian'   => $log->created_at?->format('d/m/Y H:i:s'),
                'Cấp độ'      => $log->level?->label() ?? $log->level,
                'Module'      => $log->module,
                'Action'      => $log->action,
                'Actor'       => $log->actor_name ?? ($log->causer_id ? "User#{$log->causer_id}" : 'system'),
                'Actor IP'    => $log->actor_ip,
                'Subject'     => $log->subject_type ? "{$log->subject_type}#{$log->subject_id}" : null,
                'Label'       => $log->subject_label,
                'Mô tả'       => $log->description,
                'Request ID'  => $log->request_id,
                'URL'         => $log->http?->url,
                'Status'      => $log->http?->status_code,
                'Duration ms' => $log->http?->duration_ms,
            ]);

        (new FastExcel($collection))->export($path);

        Cache::put("actlog:export:{$exportKey}", $path, 3600);
    }

    private function applyFilters(Builder $q, array $filters): void
    {
        if (!empty($filters['organization_id'])) $q->where('organization_id', $filters['organization_id']);
        if (!empty($filters['module']))          $q->where('module', $filters['module']);
        if (!empty($filters['action']))          $q->where('action', $filters['action']);
        if (!empty($filters['level_min']))       $q->where('level', '>=', $filters['level_min']);
        if (!empty($filters['actor_id']))        $q->where('causer_id', $filters['actor_id']);
        if (!empty($filters['date_from']))       $q->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        if (!empty($filters['date_to']))         $q->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        if (!empty($filters['search'])) {
            $t = '%' . $filters['search'] . '%';
            $q->where(fn ($q2) => $q2->where('description', 'like', $t)
                                     ->orWhere('action', 'like', $t));
        }
    }
}
