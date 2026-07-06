@extends('layouts.backend')
@section('title', 'Cấu hình Capabilities — ' . $organizationSolution->name)

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
    <p class="text-sm text-base-content/50 mb-4">Bước 3: chọn Capability nào được bật cho tổ chức.</p>

    @include('organizationsolution::wizard._nav')

    <form method="POST" action="{{ route('organization_solutions.wizard.capabilities', $organizationSolution) }}">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                @php $configs = $organizationSolution->capabilityConfigs->keyBy('blueprint_capability_id'); @endphp
                @forelse ($organizationSolution->blueprintVersion->capabilities as $index => $capability)
                @php $config = $configs->get($capability->id); @endphp
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <input type="hidden" name="items[{{ $index }}][blueprint_capability_id]" value="{{ $capability->id }}">
                    <input type="checkbox" name="items[{{ $index }}][enabled]" value="1"
                           class="checkbox checkbox-sm" @checked($config->enabled ?? true)>
                    <div class="flex-1">
                        <span class="font-medium text-sm">{{ $capability->name }}</span>
                        <span class="text-xs text-base-content/40 font-mono ml-1">{{ $capability->code }}</span>
                    </div>
                    <input type="text" name="items[{{ $index }}][override_name]" placeholder="Tên tuỳ chỉnh (tuỳ chọn)"
                           value="{{ $config->override_name ?? '' }}" class="input input-bordered input-xs w-56">
                </div>
                @empty
                <p class="text-sm text-base-content/40">Blueprint chưa có Capability nào.</p>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Lưu & tiếp tục</button>
        </div>
    </form>
</div>
@endsection
