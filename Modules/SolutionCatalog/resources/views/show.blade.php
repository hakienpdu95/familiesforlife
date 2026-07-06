@extends('layouts.backend')
@section('title', $businessSolution->name)

@section('content')
<div class="max-w-4xl">
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

    <a href="{{ route('solution_catalog.index') }}" class="btn btn-ghost btn-sm mb-4">← Danh mục</a>

    @php $blueprint = $businessSolution->blueprints->first(); $version = $blueprint?->currentVersion; @endphp

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <span class="badge badge-ghost badge-sm">{{ $businessSolution->vertical?->name }}</span>
            <h1 class="text-2xl font-bold text-base-content mt-1">{{ $businessSolution->name }}</h1>
            <p class="text-sm text-base-content/60 mt-1">{{ $businessSolution->short_description }}</p>
        </div>

        @can(\App\Enums\PermissionEnum::SOLUTION_ACTIVATE->value)
        @if ($alreadyActivated)
        <span class="btn btn-disabled btn-sm">Đã kích hoạt</span>
        @elseif ($blueprint && $version)
        <form method="POST" action="{{ route('solution_catalog.activate', $businessSolution) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">Kích hoạt</button>
        </form>
        @else
        <span class="btn btn-disabled btn-sm" title="Chưa có Blueprint published">Chưa thể kích hoạt</span>
        @endif
        @endcan
    </div>

    @if ($businessSolution->description)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <p class="text-sm whitespace-pre-line">{{ $businessSolution->description }}</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Đối tượng phù hợp</h2>
                <div class="flex flex-wrap gap-1">
                    @forelse ($businessSolution->target_customers ?? [] as $customer)
                    <span class="badge badge-outline badge-sm">{{ $customer }}</span>
                    @empty
                    <span class="text-sm text-base-content/40">Chưa khai báo.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Thông tin Blueprint</h2>
                @if ($blueprint)
                <p class="text-sm">Version hiện hành: <span class="font-mono">{{ $version?->version }}</span></p>
                <p class="text-sm text-base-content/60">Author: {{ $blueprint->createdBy?->name ?? '—' }}</p>
                @else
                <p class="text-sm text-base-content/40">Chưa có Blueprint published.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Business Outcomes</h2>
                <ul class="text-sm space-y-1">
                    @forelse ($version?->outcomes ?? [] as $outcome)
                    <li>
                        <span class="font-medium">{{ $outcome->name }}</span>
                        @if ($outcome->success_metric)
                        <div class="text-xs text-base-content/50">{{ $outcome->success_metric }}</div>
                        @endif
                    </li>
                    @empty
                    <li class="text-base-content/40">Chưa có outcome nào.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">AI Capabilities</h2>
                <ul class="text-sm space-y-1">
                    @forelse ($version?->aiCapabilities ?? [] as $capability)
                    <li>
                        <span class="font-medium">{{ $capability->name }}</span>
                        <span class="text-xs text-base-content/40 font-mono ml-1">{{ $capability->capability_code }}</span>
                    </li>
                    @empty
                    <li class="text-base-content/40">Chưa có AI capability nào.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
