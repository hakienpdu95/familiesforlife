@extends('layouts.backend')
@section('title', 'Cấu hình Workflows — ' . $organizationSolution->name)

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
    <p class="text-sm text-base-content/50 mb-4">Bước 4: chọn Workflow nào được bật, SLA và người phụ trách mặc định.</p>

    @include('organizationsolution::wizard._nav')

    <form method="POST" action="{{ route('organization_solutions.wizard.workflows', $organizationSolution) }}">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                @php
                    $configs = $organizationSolution->workflowConfigs->keyBy('blueprint_workflow_id');
                    $allWorkflows = $organizationSolution->blueprintVersion->capabilities->flatMap->workflows;
                @endphp
                @forelse ($allWorkflows as $index => $workflow)
                @php $config = $configs->get($workflow->id); @endphp
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <input type="hidden" name="items[{{ $index }}][blueprint_workflow_id]" value="{{ $workflow->id }}">
                    <input type="checkbox" name="items[{{ $index }}][enabled]" value="1"
                           class="checkbox checkbox-sm" @checked($config->enabled ?? true)>
                    <div class="flex-1">
                        <span class="font-medium text-sm">{{ $workflow->name }}</span>
                        <span class="text-xs text-base-content/40 font-mono ml-1">{{ $workflow->code }}</span>
                    </div>
                    <input type="number" name="items[{{ $index }}][sla_days]" placeholder="SLA (ngày)"
                           value="{{ $config->sla_days ?? '' }}" class="input input-bordered input-xs w-28" min="0">
                </div>
                @empty
                <p class="text-sm text-base-content/40">Blueprint chưa có Workflow nào.</p>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Lưu & tiếp tục</button>
        </div>
    </form>
</div>
@endsection
