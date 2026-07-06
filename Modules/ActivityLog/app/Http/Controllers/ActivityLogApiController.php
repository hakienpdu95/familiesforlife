<?php

namespace Modules\ActivityLog\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ActivityLog\Models\ActivityLog;
use App\Shared\Tenancy\TenantContext;

class ActivityLogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $v = $request->validate([
            'module'          => 'nullable|string|max:64',
            'action'          => 'nullable|string|max:128',
            'level_min'       => 'nullable|integer|min:1|max:5',
            'actor_id'        => 'nullable|integer',
            'subject_type'    => 'nullable|string|max:255',
            'subject_id'      => 'nullable|integer',
            'request_id'      => 'nullable|string|max:36',
            'search'          => 'nullable|string|max:100',
            'date_from'       => 'nullable|date',
            'date_to'         => 'nullable|date',
            'page'            => 'nullable|integer|min:1',
            'size'            => 'nullable|integer|min:1|max:100',
            'sorters'         => 'nullable|array',
            'sorters.*.field' => 'nullable|string',
            'sorters.*.dir'   => 'nullable|in:asc,desc',
        ]);

        $page = max(0, ((int) ($v['page'] ?? 1)) - 1);
        $size = (int) ($v['size'] ?? 20);

        $allowedSortFields = ['created_at', 'level', 'module', 'action', 'log_name', 'event'];
        $firstSorter = $v['sorters'][0] ?? null;
        $sortField   = in_array($firstSorter['field'] ?? '', $allowedSortFields, true)
                        ? $firstSorter['field']
                        : 'created_at';
        $sortDir     = in_array(strtolower($firstSorter['dir'] ?? ''), ['asc', 'desc'], true)
                        ? strtolower($firstSorter['dir'])
                        : 'desc';

        $query = ActivityLog::query();

        if (TenantContext::isSet()) {
            $query->forOrganization(TenantContext::getOrganizationId());
        }

        // Module filter: match custom module column OR Spatie log_name
        if (!empty($v['module'])) {
            $m = $v['module'];
            $query->where(fn ($q) => $q->where('module', $m)->orWhere('log_name', strtolower($m)));
        }
        // Action filter: match custom action column OR Spatie event
        if (!empty($v['action'])) {
            $a = $v['action'];
            $query->where(fn ($q) => $q->where('action', $a)->orWhere('event', $a));
        }
        if (!empty($v['level_min']))   $query->where('level', '>=', $v['level_min']);
        if (!empty($v['actor_id']))    $query->where('causer_id', $v['actor_id']);
        if (!empty($v['request_id'])) $query->where('request_id', $v['request_id']);
        if (!empty($v['subject_type']) && !empty($v['subject_id'])) {
            $query->where('subject_type', $v['subject_type'])
                  ->where('subject_id', $v['subject_id']);
        }
        if (!empty($v['date_from'])) $query->where('created_at', '>=', $v['date_from'] . ' 00:00:00');
        if (!empty($v['date_to']))   $query->where('created_at', '<=', $v['date_to'] . ' 23:59:59');
        if (!empty($v['search'])) {
            // Chỉ search trên actor_name (có index qua compound) và action/subject_label.
            // Bỏ description và event để tránh full-scan trên cột TEXT lớn.
            $t = '%' . $v['search'] . '%';
            $query->where(fn ($q) => $q->where('actor_name', 'like', $t)
                                       ->orWhere('action', 'like', $t)
                                       ->orWhere('subject_label', 'like', $t));
        }

        $total = $query->count();
        $rows  = $query
            ->orderBy($sortField, $sortDir)
            ->offset($page * $size)
            ->limit($size)
            ->get([
                'id', 'log_name', 'description', 'subject_type', 'subject_id', 'subject_label',
                'causer_id', 'causer_type', 'event', 'level', 'module', 'action', 'request_id',
                'actor_name', 'actor_ip', 'created_at', 'properties',
            ]);

        // Batch-load names for rows where actor_name is null but causer_id exists
        $missingIds = $rows->whereNull('actor_name')->whereNotNull('causer_id')
                           ->pluck('causer_id')->unique()->values();
        $userMap = $missingIds->isNotEmpty()
            ? User::whereIn('id', $missingIds)->pluck('name', 'id')->all()
            : [];

        $data = $rows->map(fn ($log) => $this->normalizeRow($log, $userMap));

        return response()->json([
            'data'      => $data,
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
        ]);
    }

    private function normalizeRow(ActivityLog $log, array $userMap): array
    {
        $rawLevel = $log->getRawOriginal('level');
        $levelInt = is_numeric($rawLevel) ? (int) $rawLevel : 2;

        // Actor
        $displayActor = $log->actor_name
            ?? ($userMap[$log->causer_id] ?? null)
            ?? ($log->causer_id ? 'User #' . $log->causer_id : null)
            ?? 'System';

        $actorIsUser = $log->causer_type && str_ends_with($log->causer_type, 'User');

        // Module: custom value → log_name capitalized → '-'
        $displayModule = $log->module ?: ucfirst($log->log_name ?: '-');

        // Action: custom value → Spatie event → description → '-'
        $displayAction = $log->action ?: ($log->event ?: ($log->description ?: '-'));

        // Subject
        $subjectShort   = $log->subject_type ? class_basename($log->subject_type) : null;
        $displaySubject = $log->subject_label
            ?: ($subjectShort && $log->subject_id ? "{$subjectShort} #{$log->subject_id}" : null)
            ?: $subjectShort
            ?: null;

        // Properties preview — flatten non-array keys, skip Spatie internal nested keys
        $props = json_decode($log->getRawOriginal('properties') ?? '{}', true) ?? [];
        $flat  = collect($props)
            ->reject(fn ($v) => is_array($v))  // skip attributes/old nested objects
            ->map(function ($v, $k) {
                if (is_bool($v)) return "{$k}: " . ($v ? 'true' : 'false');
                if (is_null($v)) return null;
                $str = (string) $v;
                return "{$k}: " . (mb_strlen($str) > 40 ? mb_substr($str, 0, 40) . '…' : $str);
            })
            ->filter()
            ->take(4)
            ->values()
            ->implode(' · ');

        // Enhanced description: merge description + props preview
        $description = $log->description ?: null;

        return [
            'id'              => $log->id,
            'created_at'      => $log->created_at,
            'level'           => $levelInt,
            'display_module'  => $displayModule,
            'display_action'  => $displayAction,
            'display_actor'   => $displayActor,
            'actor_type'      => $actorIsUser ? 'user' : ($log->causer_type ? 'system' : 'anonymous'),
            'actor_ip'        => $log->actor_ip,
            'display_subject' => $displaySubject,
            'subject_type'    => $log->subject_type,
            'subject_id'      => $log->subject_id,
            'description'     => $description,
            'props_preview'   => $flat ?: null,
            'request_id'      => $log->request_id,
        ];
    }

    public function stats(Request $request): JsonResponse
    {
        $days  = min(90, max(1, (int) $request->input('days', 30)));
        $from  = now()->subDays($days);
        $orgId = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;

        $base = ActivityLog::where('created_at', '>=', $from)
            ->when($orgId, fn ($q) => $q->forOrganization($orgId));

        $todayCounts = ActivityLog::whereDate('created_at', today())
            ->when($orgId, fn ($q) => $q->forOrganization($orgId))
            ->selectRaw('
                SUM(CASE WHEN level >= 4 THEN 1 ELSE 0 END) as error_today,
                SUM(CASE WHEN level  = 5 THEN 1 ELSE 0 END) as critical_today
            ')
            ->first();

        return response()->json([
            'by_level'       => (clone $base)->selectRaw('level, COUNT(*) as count')->groupBy('level')->get(),
            'by_module'      => (clone $base)->selectRaw(
                                    "COALESCE(NULLIF(module,''), log_name) as display_module, COUNT(*) as count"
                                )->groupByRaw("COALESCE(NULLIF(module,''), log_name)")
                                ->orderByDesc('count')->limit(10)->get(),
            'by_day'         => (clone $base)->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                    ->groupBy('date')->orderBy('date')->get(),
            'error_today'    => (int) ($todayCounts->error_today    ?? 0),
            'critical_today' => (int) ($todayCounts->critical_today ?? 0),
        ]);
    }

    public function meta(): JsonResponse
    {
        $orgId = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
        $base  = ActivityLog::when($orgId, fn ($q) => $q->forOrganization($orgId));

        // Merge custom modules + Spatie log_names into one list
        $customModules  = (clone $base)->whereNotNull('module')->where('module', '!=', '')
            ->distinct()->pluck('module');
        $spatieModules  = (clone $base)->whereNull('module')->whereNotNull('log_name')
            ->where('log_name', '!=', '')
            ->distinct()->pluck('log_name')
            ->map(fn ($n) => ucfirst($n));
        $modules = $customModules->merge($spatieModules)->unique()->sort()->values();

        // Merge actions per module: custom actions + Spatie events grouped by log_name/module
        $customActions = (clone $base)->whereNotNull('module')->whereNotNull('action')
            ->where('action', '!=', '')
            ->distinct()->select('module', 'action')->orderBy('action')
            ->get()->groupBy('module');

        $spatieActions = (clone $base)->whereNull('module')->whereNotNull('event')
            ->where('event', '!=', '')
            ->distinct()->select('log_name', 'event')
            ->orderBy('event')->get()
            ->groupBy(fn ($r) => ucfirst($r->log_name))
            ->map(fn ($items) => $items->map(fn ($r) => (object)['action' => $r->event]));

        $actions = $customActions->toBase()->merge($spatieActions);

        return response()->json(compact('modules', 'actions'));
    }
}
