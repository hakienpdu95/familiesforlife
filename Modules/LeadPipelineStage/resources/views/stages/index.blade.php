@extends('layouts.backend')
@section('title', 'Pipeline Stages')


@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Pipeline Stages</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý các tình trạng cơ hội theo tổ chức</p>
    </div>
    @can('create', \Modules\LeadPipelineStage\Models\LeadPipelineStage::class)
    <a href="{{ route('lead-pipeline-stage.create') }}" class="btn btn-primary btn-sm">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Thêm tình trạng
    </a>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success mb-4 py-2"><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2"><span>{{ session('error') }}</span></div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead class="bg-base-200/40">
                <tr>
                    <th class="w-12 text-center">STT</th>
                    <th>Tình trạng</th>
                    <th class="w-24">Mã</th>
                    <th class="w-28">Màu</th>
                    <th class="w-20 text-center">Xác suất</th>
                    <th class="w-16 text-center">Thắng</th>
                    <th class="w-16 text-center">Thua</th>
                    <th class="w-20 text-center">Phạm vi</th>
                    <th class="w-24 text-center">Trạng thái</th>
                    <th class="w-28 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stages as $stage)
                <tr class="{{ !$stage->is_active ? 'opacity-50' : '' }}">
                    <td class="text-center text-base-content/50 text-xs">{{ $stage->sort_order }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $stage->color }}"></div>
                            <span class="font-medium">{{ $stage->label }}</span>
                        </div>
                    </td>
                    <td class="font-mono text-xs text-base-content/60">{{ $stage->code }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded border border-base-300" style="background-color: {{ $stage->color }}"></div>
                            <span class="text-xs text-base-content/60">{{ $stage->color }}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-ghost badge-sm">{{ $stage->probability }}%</span>
                    </td>
                    <td class="text-center">
                        @if($stage->is_won)
                        <span class="badge badge-success badge-sm">✓ Won</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($stage->is_lost)
                        <span class="badge badge-error badge-sm">✓ Lost</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($stage->is_global)
                        <span class="badge badge-neutral badge-sm">Global</span>
                        @else
                        <span class="badge badge-outline badge-sm">Org</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @can('update', $stage)
                        <form method="POST" action="{{ route('lead-pipeline-stage.toggle', $stage) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-ghost btn-xs">
                                @if($stage->is_active)
                                <span class="badge badge-success badge-sm">Hoạt động</span>
                                @else
                                <span class="badge badge-ghost badge-sm">Ẩn</span>
                                @endif
                            </button>
                        </form>
                        @else
                        @if($stage->is_active)
                        <span class="badge badge-success badge-sm">Hoạt động</span>
                        @else
                        <span class="badge badge-ghost badge-sm">Ẩn</span>
                        @endif
                        @endcan
                    </td>
                    <td class="text-right">
                        @if(!$stage->is_global)
                        <div class="flex justify-end gap-1">
                            @can('update', $stage)
                            <a href="{{ route('lead-pipeline-stage.edit', $stage) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            @endcan
                            @can('delete', $stage)
                            <form method="POST" action="{{ route('lead-pipeline-stage.destroy', $stage) }}"
                                  onsubmit="return confirm('Xóa tình trạng «{{ $stage->label }}»?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                            </form>
                            @endcan
                        </div>
                        @else
                        <span class="text-xs text-base-content/30 italic">Hệ thống</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-12 text-center text-base-content/40">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                        Chưa có tình trạng nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
