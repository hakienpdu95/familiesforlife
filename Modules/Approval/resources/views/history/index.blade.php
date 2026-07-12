@extends('layouts.backend')
@section('title', 'Lịch sử duyệt')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Lịch sử duyệt</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Toàn bộ hành động duyệt nội dung (mọi loại sản phẩm/entity, mọi trạng thái) — dành cho giám sát/kiểm tra.
        </p>
    </div>
    <a href="{{ route('backend.approval.dashboard') }}" class="btn btn-ghost btn-sm">Chờ duyệt của tôi</a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-5">
    <select name="subject_type" class="select select-bordered select-sm">
        <option value="">— Tất cả loại —</option>
        @foreach (config('approval.subjects', []) as $type => $cfg)
            <option value="{{ $type }}" @selected($subjectTypeFilter === $type)>{{ $cfg['label'] ?? $type }}</option>
        @endforeach
    </select>
    <select name="action" class="select select-bordered select-sm">
        <option value="">— Tất cả hành động —</option>
        @foreach (['submit' => 'Gửi duyệt', 'approve' => 'Duyệt', 'reject' => 'Từ chối', 'publish' => 'Xuất bản', 'archive' => 'Lưu trữ', 'revise' => 'Sửa nội dung'] as $value => $label)
            <option value="{{ $value }}" @selected($actionFilter === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn btn-sm btn-neutral">Lọc</button>
    @if ($subjectTypeFilter || $actionFilter)
        <a href="{{ route('backend.approval.history') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
    @endif
</form>

<div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                <tr>
                    <th>Thời gian</th>
                    <th>Entity</th>
                    <th>Hành động</th>
                    <th>Chuyển trạng thái</th>
                    <th>Người thực hiện</th>
                    <th>Lý do</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                @php($entity = $log->subject?->subject)
                <tr class="hover">
                    <td class="text-xs text-base-content/50 whitespace-nowrap">
                        {{ $log->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-sm">
                        @if ($entity)
                            <span class="font-medium">{{ class_basename($entity) }} #{{ $entity->id }}</span>
                            @if (($entity->name ?? null))
                                <span class="text-base-content/60"> — {{ $entity->name }}</span>
                            @endif
                            @if (method_exists($entity, 'getAttribute') && ($entity->approvalDashboardUrl ?? null))
                                <a href="{{ $entity->approvalDashboardUrl }}" class="link link-primary text-xs ml-1">Xem</a>
                            @endif
                        @else
                            <span class="text-base-content/30 italic">Entity đã bị xoá</span>
                        @endif
                    </td>
                    <td class="text-sm">{{ $log->actionLabel() }}</td>
                    <td class="text-xs">
                        @if ($log->from_status)
                            <span class="badge badge-ghost badge-sm">{{ \Modules\Approval\Enums\ApprovalStatus::tryFrom($log->from_status)?->label() ?? $log->from_status }}</span>
                            →
                        @endif
                        <span class="badge badge-sm {{ \Modules\Approval\Enums\ApprovalStatus::tryFrom($log->to_status)?->badgeClass() }}">
                            {{ \Modules\Approval\Enums\ApprovalStatus::tryFrom($log->to_status)?->label() ?? $log->to_status }}
                        </span>
                    </td>
                    <td class="text-sm text-base-content/60">
                        {{ $log->performedBy?->name ?? 'Hệ thống (job/command)' }}
                    </td>
                    <td class="text-sm text-base-content/60 max-w-xs truncate" title="{{ $log->reason }}">
                        {{ $log->reason ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-base-content/40">Chưa có lịch sử duyệt nào.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($logs->hasPages())
    <div class="p-3 border-t border-base-200">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
