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
    /** Field key → nhãn tiếng Việt, dùng để dịch tên cột DB thành câu mô tả đọc được. */
    private const FIELD_LABELS = [
        'title' => 'tiêu đề', 'name' => 'tên', 'label' => 'nhãn', 'slug' => 'đường dẫn',
        'content' => 'nội dung', 'excerpt' => 'tóm tắt', 'description' => 'mô tả',
        'status' => 'trạng thái', 'published_at' => 'thời điểm xuất bản',
        'is_system' => 'cờ hệ thống', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description',
        'seo_noindex' => 'noindex', 'sort_order' => 'thứ tự', 'order_column' => 'thứ tự',
        'view_count' => 'lượt xem', 'click_count' => 'lượt click', 'email' => 'email',
        'phone' => 'số điện thoại', 'price' => 'giá', 'is_active' => 'trạng thái hoạt động',
        'active' => 'trạng thái hoạt động', 'template' => 'thiết kế (template)', 'url' => 'đường dẫn URL',
        'link_url' => 'đường dẫn liên kết', 'placement' => 'vị trí hiển thị', 'target_type' => 'loại đối tượng liên kết',
        'link_type' => 'loại liên kết', 'parent_id' => 'mục cha', 'category_id' => 'danh mục',
        'note' => 'ghi chú', 'address' => 'địa chỉ', 'password' => 'mật khẩu', 'role' => 'vai trò',
        'permission' => 'quyền', 'image' => 'hình ảnh', 'avatar' => 'ảnh đại diện',
        'assigned_to' => 'người được giao', 'stage_id' => 'giai đoạn', 'start_date' => 'ngày bắt đầu',
        'end_date' => 'ngày kết thúc', 'alt_text' => 'văn bản thay thế', 'badge_label' => 'nhãn badge',
        'open_in_new_tab' => 'mở tab mới', 'icon' => 'icon', 'depth' => 'cấp độ',
    ];

    /** Cột kỹ thuật/audit — không đưa vào câu mô tả "đã đổi trường nào". */
    private const NOISE_KEYS = [
        'id', 'uuid', 'created_at', 'updated_at', 'deleted_at',
        'created_by', 'updated_by', 'organization_id',
    ];


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
                'actor_name', 'actor_ip', 'created_at', 'attribute_changes',
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

        // attribute_changes (Spatie LogsActivity, cột riêng từ v4 — KHÔNG còn nằm trong
        // 'properties'): {"attributes": {...trạng thái mới}, "old": {...trạng thái cũ}}. Rỗng
        // đối với log ghi qua ActivityLogger/WriteActivityLogAction (module tự quản context riêng
        // qua bảng activity_log_contexts).
        $changes    = $log->attribute_changes;
        $attributes = is_array($changes?->get('attributes')) ? $changes->get('attributes') : [];
        $old        = is_array($changes?->get('old')) ? $changes->get('old') : [];

        $event = $log->event ?: $log->action ?: '';

        // Subject: "{Module} - #{ID} - {Tên/Tiêu đề}" — tên lấy từ subject_label (log ghi qua
        // ActivityLogger, đã resolve qua getActivityLabel()/name/title) hoặc từ chính properties
        // (log tự động của Spatie LogsActivity, không có subject_label).
        $subjectShort = $log->subject_type ? class_basename($log->subject_type) : null;
        $subjectName  = $log->subject_label
            ?: ($attributes['title'] ?? $attributes['name'] ?? $attributes['label'] ?? $attributes['email'] ?? null);

        $displaySubject = implode(' - ', array_filter([
            $subjectShort,
            $log->subject_id ? "#{$log->subject_id}" : null,
            $subjectName,
        ])) ?: null;

        $description   = $this->buildDescription($log->description, $event, $attributes, $subjectName);
        $propsPreview  = $this->buildPropsPreview($attributes, $old, $event);

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
            'subject_module'  => $subjectShort,
            'subject_name'    => $subjectName,
            'subject_type'    => $log->subject_type,
            'subject_id'      => $log->subject_id,
            'description'     => $description,
            'props_preview'   => $propsPreview,
            'request_id'      => $log->request_id,
        ];
    }

    /**
     * Sinh mô tả có ý nghĩa. Log ghi qua ActivityLogger với $description tường minh (không rỗng,
     * không phải tên event thô) được giữ nguyên — coder gọi nơi đó đã chủ động viết câu rõ nghĩa.
     * Ngược lại (log tự động của Spatie LogsActivity, description chỉ là 'created'/'updated'/
     * 'deleted', hoặc log qua BaseModelObserver có description dạng "resource.updated") — suy ra
     * câu mô tả từ event + danh sách field đã đổi (properties.attributes).
     */
    private function buildDescription(?string $rawDescription, string $event, array $attributes, ?string $subjectName): ?string
    {
        $isGeneric = $rawDescription === null
            || $rawDescription === ''
            || $rawDescription === $event
            || preg_match('/^[a-z0-9_]*\.?(created|updated|deleted)$/i', $rawDescription) === 1;

        if (!$isGeneric) {
            return $rawDescription;
        }

        $changedFields = collect(array_keys($attributes))
            ->reject(fn ($key) => in_array($key, self::NOISE_KEYS, true))
            ->map(fn ($key) => self::FIELD_LABELS[$key] ?? str_replace('_', ' ', $key))
            ->values();

        $quotedName = $subjectName ? " \"{$subjectName}\"" : '';

        return match (true) {
            str_contains($event, 'delete') => "Xoá{$quotedName}",
            str_contains($event, 'create') => "Tạo mới{$quotedName}",
            str_contains($event, 'update') && $changedFields->isNotEmpty() =>
                'Cập nhật ' . $changedFields->implode(', '),
            str_contains($event, 'update') => "Cập nhật thông tin{$quotedName}",
            $event !== '' => ucfirst(str_replace(['_', '.'], [' ', ' — '], $event)),
            default => null,
        };
    }

    /**
     * "field: cũ → mới" cho các field đã đổi (dựa vào properties.old của Spatie khi có), hoặc
     * "field: giá trị" khi không có state cũ (created, hoặc log không phải Spatie auto-log).
     */
    private function buildPropsPreview(array $attributes, array $old, string $event): ?string
    {
        $keys = collect(array_keys($attributes))->reject(fn ($key) => in_array($key, self::NOISE_KEYS, true));
        if ($keys->isEmpty()) {
            return null;
        }

        $isUpdate = str_contains($event, 'update') && !empty($old);

        $preview = $keys->take(4)->map(function ($key) use ($attributes, $old, $isUpdate) {
            $new = $this->scalarPreview($attributes[$key] ?? null);
            if ($isUpdate && array_key_exists($key, $old)) {
                return "{$key}: " . $this->scalarPreview($old[$key] ?? null) . " → {$new}";
            }

            return "{$key}: {$new}";
        })->implode(' · ');

        return $preview ?: null;
    }

    private function scalarPreview(mixed $value): string
    {
        if (is_bool($value))  return $value ? 'true' : 'false';
        if (is_null($value))  return '∅';
        if (is_array($value)) return '[...]';

        $str = (string) $value;

        return mb_strlen($str) > 40 ? mb_substr($str, 0, 40) . '…' : $str;
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
