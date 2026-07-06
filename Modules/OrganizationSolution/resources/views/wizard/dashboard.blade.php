@extends('layouts.backend')
@section('title', 'Cấu hình Dashboard — ' . $organizationSolution->name)

@section('content')
<div x-data="{ widgets: {{ $organizationSolution->dashboardWidgets->isNotEmpty() ? $organizationSolution->dashboardWidgets->count() : 1 }} }">
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
        @endif
    @endforeach
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <h1 class="text-2xl font-bold text-base-content mb-1">{{ $organizationSolution->name }}</h1>
    <p class="text-sm text-base-content/50 mb-4">Bước 7: chọn widget hiển thị trên Dashboard của tổ chức (nguồn từ Blueprint Analytics).</p>

    @include('organizationsolution::wizard._nav')

    <form method="POST" action="{{ route('organization_solutions.wizard.dashboard', $organizationSolution) }}">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                @forelse ($organizationSolution->dashboardWidgets as $index => $widget)
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <input type="text" name="items[{{ $index }}][title]" value="{{ $widget->title }}"
                           placeholder="Tiêu đề widget" class="input input-bordered input-xs flex-1" required>
                    <select name="items[{{ $index }}][blueprint_analytic_id]" class="select select-bordered select-xs w-56">
                        <option value="">— Không gắn metric —</option>
                        @foreach ($organizationSolution->blueprintVersion->analytics as $metric)
                        <option value="{{ $metric->id }}" @selected($widget->blueprint_analytic_id === $metric->id)>{{ $metric->name }}</option>
                        @endforeach
                    </select>
                </div>
                @empty
                <p class="text-sm text-base-content/40 mb-2">Chưa có widget nào — thêm ít nhất 1 để hoàn tất bước này.</p>
                <div class="flex items-center gap-3 py-2">
                    <input type="text" name="items[0][title]" placeholder="Tiêu đề widget" class="input input-bordered input-xs flex-1">
                    <select name="items[0][blueprint_analytic_id]" class="select select-bordered select-xs w-56">
                        <option value="">— Không gắn metric —</option>
                        @foreach ($organizationSolution->blueprintVersion->analytics as $metric)
                        <option value="{{ $metric->id }}">{{ $metric->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Lưu & tiếp tục</button>
        </div>
    </form>
</div>
@endsection
