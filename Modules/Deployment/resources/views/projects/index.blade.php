@extends('layouts.backend')
@section('title', 'Dự án — ' . $vertical->label())

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Dự án {{ $vertical->label() }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Tất cả dự án thuộc vertical <span class="badge badge-outline badge-sm">{{ $vertical->code() }}</span>
            </p>
        </div>
        <a href="{{ route('deployment.projects.create', ['vertical' => $vertical->code()]) }}"
           class="btn btn-primary btn-sm">
            + Tạo dự án
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    @if($projects->isEmpty())
    <div class="card bg-base-100 border border-base-200">
        <div class="card-body items-center text-center py-12">
            <p class="text-base-content/50">Chưa có dự án nào. Tạo dự án đầu tiên để bắt đầu.</p>
            <a href="{{ route('deployment.projects.create', ['vertical' => $vertical->code()]) }}"
               class="btn btn-outline btn-sm mt-3">Tạo ngay</a>
        </div>
    </div>
    @else
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <table class="table table-sm">
            <thead class="bg-base-200 text-xs uppercase">
                <tr>
                    <th>Mã</th>
                    <th>Tên dự án</th>
                    <th>Trạng thái</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $project)
                <tr class="hover">
                    <td class="font-mono text-xs">{{ $project->code }}</td>
                    <td class="font-medium">{{ $project->name }}</td>
                    <td>
                        <span class="badge badge-sm
                            @if($project->status?->value === 'active') badge-success
                            @elseif($project->status?->value === 'completed') badge-info
                            @elseif($project->status?->value === 'on_hold') badge-warning
                            @else badge-ghost
                            @endif">
                            {{ $project->status?->label() ?? $project->status }}
                        </span>
                    </td>
                    <td class="text-sm">{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') : '—' }}</td>
                    <td class="text-sm">{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d/m/Y') : '—' }}</td>
                    <td>
                        <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code(), 'project_id' => $project->id]) }}"
                           class="btn btn-ghost btn-xs">Xem targets</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $projects->links() }}</div>
    @endif

</div>
@endsection
