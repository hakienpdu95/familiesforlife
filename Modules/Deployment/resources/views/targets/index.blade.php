@extends('layouts.backend')
@section('title', $vertical->targetLabel())

@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">{{ $vertical->targetLabel() }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Danh sách đối tượng triển khai</p>
        </div>
        @can('create', \Modules\Deployment\Models\DeploymentTarget::class)
        <a href="{{ route('deployment.targets.create', ['vertical' => $vertical->code()]) }}"
           class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm {{ $vertical->targetLabel() }}
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif

    {{-- Filters -------------------------------------------------------------}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Phase</span></label>
                    <select name="phase" class="select select-bordered select-sm w-40">
                        <option value="">Tất cả</option>
                        @foreach($phases as $phase)
                        <option value="{{ $phase }}" @selected(request('phase') === $phase)>{{ $phaseLabels[$phase] ?? $phase }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Dự án</span></label>
                    <select name="project_id" class="select select-bordered select-sm w-48">
                        <option value="">Tất cả</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Tìm kiếm</span></label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Tên hoặc MST..." class="input input-bordered input-sm w-52">
                </div>
                <button type="submit" class="btn btn-sm btn-neutral">Lọc</button>
                @if(request()->hasAny(['phase','project_id','search']))
                <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code()]) }}"
                   class="btn btn-sm btn-ghost">Xóa lọc</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table ---------------------------------------------------------------}}
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>{{ $vertical->targetLabel() }}</th>
                    <th>Dự án</th>
                    <th>Phase</th>
                    <th>Người phụ trách</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($targets as $target)
                <tr class="hover">
                    <td>
                        <div class="font-medium">{{ $target->targetOrganization?->name ?? '—' }}</div>
                        <div class="text-xs text-base-content/50">MST: {{ $target->targetOrganization?->tax_code ?? '—' }}</div>
                    </td>
                    <td class="text-sm">{{ $target->project?->name ?? '—' }}</td>
                    <td><span class="badge badge-sm badge-outline">{{ $target->current_phase }}</span></td>
                    <td class="text-sm">{{ $target->assignedEmployee?->full_name ?? '—' }}</td>
                    <td class="text-xs text-base-content/50">{{ $target->created_at?->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
                           class="btn btn-ghost btn-xs">Chi tiết</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-base-content/40">Chưa có dữ liệu</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $targets->links() }}</div>
</div>
@endsection
