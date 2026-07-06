@extends('layouts.backend')
@section('title', 'Nguồn cơ hội')


@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Nguồn cơ hội</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý các nguồn lead theo tổ chức</p>
    </div>
    @can('create', \Modules\LeadSource\Models\LeadSource::class)
    <a href="{{ route('lead-source.create') }}" class="btn btn-primary btn-sm">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Thêm nguồn
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
                    <th>Nguồn</th>
                    <th class="w-24">Mã</th>
                    <th class="w-16 text-center">Icon</th>
                    <th class="w-28">Màu</th>
                    <th class="w-20 text-center">Phạm vi</th>
                    <th class="w-24 text-center">Trạng thái</th>
                    <th class="w-28 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sources as $source)
                <tr class="{{ !$source->is_active ? 'opacity-50' : '' }}">
                    <td class="text-center text-base-content/50 text-xs">{{ $source->sort_order }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            @if($source->color)
                            <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $source->color }}"></div>
                            @endif
                            <span class="font-medium">{{ $source->label }}</span>
                        </div>
                    </td>
                    <td class="font-mono text-xs text-base-content/60">{{ $source->code }}</td>
                    <td class="text-center">
                        @if($source->icon)
                        <span class="iconify text-xl text-base-content/70" data-icon="{{ $source->icon }}"></span>
                        <div class="text-xs text-base-content/40 font-mono mt-0.5">{{ $source->icon }}</div>
                        @else
                        <span class="text-base-content/30">—</span>
                        @endif
                    </td>
                    <td>
                        @if($source->color)
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded border border-base-300" style="background-color: {{ $source->color }}"></div>
                            <span class="text-xs text-base-content/60">{{ $source->color }}</span>
                        </div>
                        @else
                        <span class="text-base-content/30">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($source->is_global)
                        <span class="badge badge-neutral badge-sm">Global</span>
                        @else
                        <span class="badge badge-outline badge-sm">Org</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @can('update', $source)
                        <form method="POST" action="{{ route('lead-source.toggle', $source) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-ghost btn-xs">
                                @if($source->is_active)
                                <span class="badge badge-success badge-sm">Hoạt động</span>
                                @else
                                <span class="badge badge-ghost badge-sm">Ẩn</span>
                                @endif
                            </button>
                        </form>
                        @else
                        @if($source->is_active)
                        <span class="badge badge-success badge-sm">Hoạt động</span>
                        @else
                        <span class="badge badge-ghost badge-sm">Ẩn</span>
                        @endif
                        @endcan
                    </td>
                    <td class="text-right">
                        @if(!$source->is_global)
                        <div class="flex justify-end gap-1">
                            @can('update', $source)
                            <a href="{{ route('lead-source.edit', $source) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            @endcan
                            @can('delete', $source)
                            <form method="POST" action="{{ route('lead-source.destroy', $source) }}"
                                  onsubmit="return confirm('Xóa nguồn «{{ $source->label }}»?')">
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
                    <td colspan="8" class="py-12 text-center text-base-content/40">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        Chưa có nguồn nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
