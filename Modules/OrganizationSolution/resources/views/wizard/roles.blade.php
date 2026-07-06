@extends('layouts.backend')
@section('title', 'Role Mapping — ' . $organizationSolution->name)

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
    <p class="text-sm text-base-content/50 mb-4">Ánh xạ role trừu tượng của Blueprint sang role/user cụ thể của tổ chức (A07 §12 — rất quan trọng).</p>

    @include('organizationsolution::wizard._nav')

    <form method="POST" action="{{ route('organization_solutions.wizard.roles', $organizationSolution) }}">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                @php $mappings = $organizationSolution->roleMappings->keyBy('blueprint_role_code'); @endphp
                @forelse ($organizationSolution->blueprintVersion->deploymentRoles as $index => $role)
                @php $mapping = $mappings->get($role->role_code); @endphp
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <input type="hidden" name="items[{{ $index }}][blueprint_role_code]" value="{{ $role->role_code }}">
                    <div class="flex-1">
                        <span class="font-medium text-sm">{{ $role->role_name }}</span>
                        <span class="text-xs text-base-content/40 font-mono ml-1">{{ $role->role_code }}</span>
                    </div>
                    <input type="number" name="items[{{ $index }}][organization_role_id]" placeholder="Role ID (Spatie)"
                           value="{{ $mapping->organization_role_id ?? '' }}" class="input input-bordered input-xs w-32">
                    <input type="number" name="items[{{ $index }}][user_id]" placeholder="hoặc User ID"
                           value="{{ $mapping->user_id ?? '' }}" class="input input-bordered input-xs w-32">
                </div>
                @empty
                <p class="text-sm text-base-content/40">Blueprint chưa có Deployment Role nào.</p>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Lưu & tiếp tục</button>
        </div>
    </form>
</div>
@endsection
