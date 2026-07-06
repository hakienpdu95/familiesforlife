@extends('layouts.backend')
@section('title', 'Issues — ' . $vertical->label())

@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">Issues</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $vertical->label() }}</p>
        </div>
        @can('create', \Modules\Deployment\Models\DeploymentIssue::class)
        <a href="{{ route('deployment.issues.create', ['vertical' => $vertical->code()]) }}"
           class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tạo Issue
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Đối tượng</span></label>
                    <select name="target_id" class="select select-bordered select-sm w-48">
                        <option value="">Tất cả</option>
                        @foreach($targets as $t)
                        <option value="{{ $t->id }}" @selected(request('target_id') == $t->id)>
                            {{ $t->targetOrganization?->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Mức độ</span></label>
                    <select name="severity" class="select select-bordered select-sm w-36">
                        <option value="">Tất cả</option>
                        @foreach($severities as $s)
                        <option value="{{ $s->value }}" @selected(request('severity') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Trạng thái</span></label>
                    <select name="status" class="select select-bordered select-sm w-36">
                        <option value="">Tất cả</option>
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if($issueTypes)
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Loại issue</span></label>
                    <select name="issue_type" class="select select-bordered select-sm w-36">
                        <option value="">Tất cả</option>
                        @foreach($issueTypes as $code => $label)
                        <option value="{{ $code }}" @selected(request('issue_type') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <button type="submit" class="btn btn-sm btn-neutral">Lọc</button>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>{{ $vertical->targetLabel() }}</th>
                    <th>Mức độ</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Người phụ trách</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($issues as $issue)
                <tr class="hover">
                    <td class="font-medium text-sm max-w-xs truncate">{{ $issue->title }}</td>
                    <td class="text-sm">{{ $issue->target?->targetOrganization?->name ?? '—' }}</td>
                    <td>
                        <span class="badge badge-sm {{ $issue->severity?->badgeClass() }}">
                            {{ $issue->severity?->label() }}
                        </span>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $issueTypes[$issue->issue_type] ?? $issue->issue_type ?? '—' }}</td>
                    <td>
                        <span class="badge badge-sm {{ $issue->status?->badgeClass() }}">
                            {{ $issue->status?->label() }}
                        </span>
                    </td>
                    <td class="text-sm">{{ $issue->owner?->name ?? '—' }}</td>
                    <td class="text-xs text-base-content/50">{{ $issue->created_at?->format('d/m/Y') }}</td>
                    <td class="flex gap-1">
                        <a href="{{ route('deployment.issues.show', ['vertical' => $vertical->code(), 'issue' => $issue->id]) }}"
                           class="btn btn-ghost btn-xs">Xem</a>
                        @if($issue->isActive())
                        @can('resolve', $issue)
                        <form method="POST" action="{{ route('deployment.issues.resolve', ['vertical' => $vertical->code(), 'issue' => $issue->id]) }}">
                            @csrf
                            <button class="btn btn-ghost btn-xs text-success">Đóng</button>
                        </form>
                        @endcan
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-8 text-base-content/40">Không có issue nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $issues->links() }}</div>
</div>
@endsection
