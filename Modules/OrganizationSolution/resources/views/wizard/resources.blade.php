@extends('layouts.backend')
@section('title', 'Cấu hình Resources — ' . $organizationSolution->name)

@section('content')
<div>
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
    <p class="text-sm text-base-content/50 mb-4">Bước 5: chỉ thay reference (VD "BM-01" → "BM-01-HTX") — không đổi Blueprint gốc.</p>

    @include('organizationsolution::wizard._nav')

    <form method="POST" action="{{ route('organization_solutions.wizard.resources', $organizationSolution) }}">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                @php $overrides = $organizationSolution->resourceOverrides->keyBy('blueprint_resource_link_id'); @endphp
                @forelse ($organizationSolution->blueprintVersion->resourceLinks as $index => $link)
                @php $override = $overrides->get($link->id); @endphp
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <input type="hidden" name="items[{{ $index }}][blueprint_resource_link_id]" value="{{ $link->id }}">
                    <div class="flex-1 font-mono text-sm">{{ $link->resource_type }}#{{ $link->resource_id }}</div>
                    <input type="text" name="items[{{ $index }}][override_reference]" placeholder="Reference riêng của tổ chức"
                           value="{{ $override->override_reference ?? '' }}" class="input input-bordered input-xs w-56">
                </div>
                @empty
                <p class="text-sm text-base-content/40">Blueprint chưa có Resource nào.</p>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Lưu & tiếp tục</button>
        </div>
    </form>
</div>
@endsection
