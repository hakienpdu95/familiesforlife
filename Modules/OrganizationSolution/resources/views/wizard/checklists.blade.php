@extends('layouts.backend')
@section('title', 'Cấu hình Checklists — ' . $organizationSolution->name)

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
    <p class="text-sm text-base-content/50 mb-4">Bước 4b: bật/tắt checklist, người thực hiện/duyệt mặc định.</p>

    @include('organizationsolution::wizard._nav')

    <form method="POST" action="{{ route('organization_solutions.wizard.checklists', $organizationSolution) }}">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                @php
                    $configs = $organizationSolution->checklistConfigs->keyBy('blueprint_checklist_id');
                    $allChecklists = $organizationSolution->blueprintVersion->capabilities->flatMap->workflows->flatMap->phases->flatMap->checklists;
                @endphp
                @forelse ($allChecklists as $index => $checklist)
                @php $config = $configs->get($checklist->id); @endphp
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <input type="hidden" name="items[{{ $index }}][blueprint_checklist_id]" value="{{ $checklist->id }}">
                    <input type="checkbox" name="items[{{ $index }}][enabled]" value="1"
                           class="checkbox checkbox-sm" @checked($config->enabled ?? true)>
                    <div class="flex-1">
                        <span class="font-medium text-sm">{{ $checklist->name }}</span>
                        <span class="text-xs text-base-content/40 font-mono ml-1">{{ $checklist->code }}</span>
                    </div>
                    <input type="number" name="items[{{ $index }}][due_days]" placeholder="Hạn (ngày)"
                           value="{{ $config->due_days ?? '' }}" class="input input-bordered input-xs w-24" min="0">
                </div>
                @empty
                <p class="text-sm text-base-content/40">Blueprint chưa có Checklist nào.</p>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Lưu & tiếp tục</button>
        </div>
    </form>
</div>
@endsection
