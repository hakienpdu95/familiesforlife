@extends('layouts.backend')
@section('title', 'Blueprint — ' . $blueprint->name . ' v' . $version->version)

@section('content')
<div>
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @php $isDraft = !in_array($version->status, ['published', 'deprecated', 'archived'], true); @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $blueprint->name }} — v{{ $version->version }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                @php
                    $badge = match($version->status) {
                        'published'  => 'badge-success',
                        'deprecated' => 'badge-warning',
                        'archived'   => 'badge-ghost',
                        default      => 'badge-outline',
                    };
                @endphp
                <span class="badge {{ $badge }} badge-sm align-middle">{{ $version->status }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('business_blueprint.admin.versions.index', $blueprint) }}" class="btn btn-ghost btn-sm">← Version Manager</a>
            <button type="button" class="btn btn-outline btn-sm"
                    onclick="bpValidate('{{ route('business_blueprint.admin.versions.validate', [$blueprint, $version]) }}')">
                Kiểm tra toàn vẹn
            </button>
            <button type="button" class="btn btn-outline btn-sm"
                    onclick="bpReadiness('{{ route('business_blueprint.admin.versions.readiness', [$blueprint, $version]) }}')">
                Readiness Checklist
            </button>
            @if ($isDraft)
            @can(\App\Enums\PermissionEnum::BLUEPRINT_PUBLISH->value)
            <form method="POST" action="{{ route('business_blueprint.admin.versions.publish', [$blueprint, $version]) }}"
                  onsubmit="return confirm('Publish version này? Version published cũ (nếu có) sẽ chuyển sang deprecated.')">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">Publish</button>
            </form>
            @endcan
            @endif
        </div>
    </div>

    <div id="result-box" class="hidden alert mb-4 text-sm"></div>

    {{-- ── Outcomes ──────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <h2 class="font-bold text-sm mb-2">Business Outcomes</h2>
            <ul class="text-sm space-y-1 mb-2">
                @forelse ($version->outcomes as $outcome)
                <li class="flex items-center justify-between border-b border-base-200 py-1">
                    <span><span class="font-mono text-xs text-base-content/40">{{ $outcome->code }}</span> {{ $outcome->name }}</span>
                    @if ($isDraft)
                    <form method="POST" action="{{ route('business_blueprint.admin.outcomes.destroy', $outcome) }}" onsubmit="return confirm('Xóa outcome này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                    </form>
                    @endif
                </li>
                @empty
                <li class="text-base-content/40">Chưa có outcome nào.</li>
                @endforelse
            </ul>
            @if ($isDraft)
            <details>
                <summary class="text-xs link link-primary cursor-pointer">+ Thêm Outcome</summary>
                <form method="POST" action="{{ route('business_blueprint.admin.outcomes.store') }}" class="flex flex-wrap gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                    <input type="text" name="code" placeholder="Mã" class="input input-bordered input-xs w-32" required>
                    <input type="text" name="name" placeholder="Tên outcome" class="input input-bordered input-xs flex-1" required>
                    <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                </form>
            </details>
            @endif
        </div>
    </div>

    {{-- ── Capabilities → Workflows → Phases → Checklists ──────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <h2 class="font-bold text-sm mb-2">Capabilities / Workflows / Phases / Checklists</h2>

            @forelse ($version->capabilities as $capability)
            <div class="border border-base-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-sm">
                        <span class="font-mono text-xs text-base-content/40">{{ $capability->code }}</span> {{ $capability->name }}
                    </span>
                    @if ($isDraft)
                    <form method="POST" action="{{ route('business_blueprint.admin.capabilities.destroy', $capability) }}" onsubmit="return confirm('Xóa capability này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                    </form>
                    @endif
                </div>

                <div class="pl-4 mt-2 space-y-2">
                    @foreach ($capability->workflows as $workflow)
                    <div class="border-l-2 border-base-300 pl-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm"><span class="font-mono text-xs text-base-content/40">{{ $workflow->code }}</span> {{ $workflow->name }}</span>
                            @if ($isDraft)
                            <form method="POST" action="{{ route('business_blueprint.admin.workflows.destroy', $workflow) }}" onsubmit="return confirm('Xóa workflow này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                            </form>
                            @endif
                        </div>

                        <div class="pl-4 mt-1 space-y-1">
                            @foreach ($workflow->phases as $phase)
                            <div class="border-l-2 border-base-200 pl-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs">
                                        <span class="font-mono text-base-content/40">{{ $phase->code }}</span> {{ $phase->name }}
                                        @if ($phase->is_initial)<span class="badge badge-info badge-xs ml-1">initial</span>@endif
                                    </span>
                                    @if ($isDraft)
                                    <form method="POST" action="{{ route('business_blueprint.admin.phases.destroy', $phase) }}" onsubmit="return confirm('Xóa phase này?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                                    </form>
                                    @endif
                                </div>
                                <ul class="pl-4 text-xs text-base-content/70">
                                    @forelse ($phase->checklists as $checklist)
                                    <li class="flex items-center justify-between py-0.5">
                                        <span><span class="font-mono text-base-content/40">{{ $checklist->code }}</span> {{ $checklist->name }}</span>
                                        @if ($isDraft)
                                        <form method="POST" action="{{ route('business_blueprint.admin.checklists.destroy', $checklist) }}" onsubmit="return confirm('Xóa checklist này?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-xs text-error">×</button>
                                        </form>
                                        @endif
                                    </li>
                                    @empty
                                    <li class="text-base-content/30">Chưa có checklist.</li>
                                    @endforelse
                                </ul>
                                @if ($isDraft)
                                <details class="pl-4">
                                    <summary class="text-xs link link-primary cursor-pointer">+ Checklist</summary>
                                    <form method="POST" action="{{ route('business_blueprint.admin.checklists.store') }}" class="flex flex-wrap gap-1 mt-1">
                                        @csrf
                                        <input type="hidden" name="phase_id" value="{{ $phase->id }}">
                                        <input type="text" name="code" placeholder="Mã" class="input input-bordered input-xs w-24" required>
                                        <input type="text" name="name" placeholder="Tên checklist" class="input input-bordered input-xs flex-1" required>
                                        <button type="submit" class="btn btn-primary btn-xs">+</button>
                                    </form>
                                </details>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @if ($isDraft)
                        <details class="pl-4 mt-1">
                            <summary class="text-xs link link-primary cursor-pointer">+ Phase</summary>
                            <form method="POST" action="{{ route('business_blueprint.admin.phases.store') }}" class="flex flex-wrap gap-1 mt-1">
                                @csrf
                                <input type="hidden" name="workflow_id" value="{{ $workflow->id }}">
                                <input type="text" name="code" placeholder="Mã" class="input input-bordered input-xs w-24" required>
                                <input type="text" name="name" placeholder="Tên phase" class="input input-bordered input-xs flex-1" required>
                                <button type="submit" class="btn btn-primary btn-xs">+</button>
                            </form>
                        </details>
                        @endif
                    </div>
                    @endforeach
                </div>
                @if ($isDraft)
                <details class="pl-4 mt-2">
                    <summary class="text-xs link link-primary cursor-pointer">+ Workflow</summary>
                    <form method="POST" action="{{ route('business_blueprint.admin.workflows.store') }}" class="flex flex-wrap gap-1 mt-1">
                        @csrf
                        <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                        <input type="hidden" name="capability_id" value="{{ $capability->id }}">
                        <input type="text" name="code" placeholder="Mã" class="input input-bordered input-xs w-24" required>
                        <input type="text" name="name" placeholder="Tên workflow" class="input input-bordered input-xs flex-1" required>
                        <button type="submit" class="btn btn-primary btn-xs">+</button>
                    </form>
                </details>
                @endif
            </div>
            @empty
            <p class="text-sm text-base-content/40">Chưa có capability nào.</p>
            @endforelse

            @if ($isDraft)
            <details class="mt-2">
                <summary class="text-xs link link-primary cursor-pointer">+ Thêm Capability</summary>
                <form method="POST" action="{{ route('business_blueprint.admin.capabilities.store') }}" class="flex flex-wrap gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                    <select name="outcome_id" class="select select-bordered select-xs">
                        <option value="">— Không gắn Outcome —</option>
                        @foreach ($version->outcomes as $outcome)
                        <option value="{{ $outcome->id }}">{{ $outcome->code }} — {{ $outcome->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="code" placeholder="Mã" class="input input-bordered input-xs w-32" required>
                    <input type="text" name="name" placeholder="Tên capability" class="input input-bordered input-xs flex-1" required>
                    <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                </form>
            </details>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- ── Deployment Roles (thay JSON default_roles) ──────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Deployment Roles</h2>
                <ul class="text-sm space-y-1 mb-2">
                    @forelse ($version->deploymentRoles as $role)
                    <li class="flex items-center justify-between border-b border-base-200 py-1">
                        <span><span class="font-mono text-xs text-base-content/40">{{ $role->role_code }}</span> {{ $role->role_name }}</span>
                        @if ($isDraft)
                        <form method="POST" action="{{ route('business_blueprint.admin.deployment_roles.destroy', $role) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                        </form>
                        @endif
                    </li>
                    @empty
                    <li class="text-base-content/40">Chưa có role nào.</li>
                    @endforelse
                </ul>
                @if ($isDraft)
                <details>
                    <summary class="text-xs link link-primary cursor-pointer">+ Thêm Role</summary>
                    <form method="POST" action="{{ route('business_blueprint.admin.deployment_roles.store') }}" class="flex flex-wrap gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                        <input type="text" name="role_code" placeholder="field_officer" class="input input-bordered input-xs w-32" required>
                        <input type="text" name="role_name" placeholder="Tên role" class="input input-bordered input-xs flex-1" required>
                        <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                    </form>
                </details>
                @endif
            </div>
        </div>

        {{-- ── Sidebar Items (thay JSON sidebar_config) ─────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Sidebar Items</h2>
                <ul class="text-sm space-y-1 mb-2">
                    @forelse ($version->sidebarItems as $item)
                    <li class="flex items-center justify-between border-b border-base-200 py-1">
                        <span><span class="font-mono text-xs text-base-content/40">{{ $item->module_key }}</span> {{ $item->label }}</span>
                        @if ($isDraft)
                        <form method="POST" action="{{ route('business_blueprint.admin.sidebar_items.destroy', $item) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                        </form>
                        @endif
                    </li>
                    @empty
                    <li class="text-base-content/40">Chưa có mục sidebar nào.</li>
                    @endforelse
                </ul>
                @if ($isDraft)
                <details>
                    <summary class="text-xs link link-primary cursor-pointer">+ Thêm mục sidebar</summary>
                    <form method="POST" action="{{ route('business_blueprint.admin.sidebar_items.store') }}" class="flex flex-wrap gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                        <input type="text" name="module_key" placeholder="module_key" class="input input-bordered input-xs w-32" required>
                        <input type="text" name="label" placeholder="Nhãn hiển thị" class="input input-bordered input-xs flex-1" required>
                        <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                    </form>
                </details>
                @endif
            </div>
        </div>

        {{-- ── Analytics ──────────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Analytics</h2>
                <ul class="text-sm space-y-1 mb-2">
                    @forelse ($version->analytics as $metric)
                    <li class="flex items-center justify-between border-b border-base-200 py-1">
                        <span><span class="font-mono text-xs text-base-content/40">{{ $metric->metric_code }}</span> {{ $metric->name }}</span>
                        @if ($isDraft)
                        <form method="POST" action="{{ route('business_blueprint.admin.analytics.destroy', $metric) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                        </form>
                        @endif
                    </li>
                    @empty
                    <li class="text-base-content/40">Chưa có metric nào.</li>
                    @endforelse
                </ul>
                @if ($isDraft)
                <details>
                    <summary class="text-xs link link-primary cursor-pointer">+ Thêm Metric</summary>
                    <form method="POST" action="{{ route('business_blueprint.admin.analytics.store') }}" class="flex flex-wrap gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                        <input type="text" name="metric_code" placeholder="metric_code" class="input input-bordered input-xs w-32" required>
                        <input type="text" name="name" placeholder="Tên metric" class="input input-bordered input-xs flex-1" required>
                        <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                    </form>
                </details>
                @endif
            </div>
        </div>

        {{-- ── Resource Links ─────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Resource Links</h2>
                <ul class="text-sm space-y-1 mb-2">
                    @php $allResourceLinks = $version->capabilities->flatMap->workflows->flatMap->phases->flatMap->checklists->flatMap->resourceLinks; @endphp
                    @forelse ($allResourceLinks as $link)
                    <li class="flex items-center justify-between border-b border-base-200 py-1">
                        <span class="font-mono text-xs">{{ $link->resource_type }}#{{ $link->resource_id }}</span>
                        @if ($isDraft)
                        <form method="POST" action="{{ route('business_blueprint.admin.resource_links.destroy', $link) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                        </form>
                        @endif
                    </li>
                    @empty
                    <li class="text-base-content/40">Chưa có resource nào.</li>
                    @endforelse
                </ul>
                @if ($isDraft)
                <details>
                    <summary class="text-xs link link-primary cursor-pointer">+ Thêm Resource</summary>
                    <form method="POST" action="{{ route('business_blueprint.admin.resource_links.store') }}" class="flex flex-wrap gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="blueprint_version_id" value="{{ $version->id }}">
                        <select name="resource_type" class="select select-bordered select-xs">
                            <option value="sop">sop</option>
                            <option value="knowledge">knowledge</option>
                            <option value="dataset">dataset</option>
                            <option value="template">template</option>
                        </select>
                        <input type="number" name="resource_id" placeholder="ID" class="input input-bordered input-xs w-20" required>
                        <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                    </form>
                </details>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bpValidate(url) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var box = document.getElementById('result-box');
            box.classList.remove('hidden', 'alert-success', 'alert-error');
            if (data.valid) {
                box.classList.add('alert-success');
                box.textContent = 'Cây Blueprint hợp lệ — không có node mồ côi, không trùng code.';
            } else {
                box.classList.add('alert-error');
                var html = '<ul class="list-disc list-inside">';
                data.errors.forEach(function (e) {
                    html += '<li>' + e.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</li>';
                });
                html += '</ul>';
                box.innerHTML = html;
            }
        });
}

function bpReadiness(url) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var box = document.getElementById('result-box');
            box.classList.remove('hidden', 'alert-success', 'alert-error');
            box.classList.add(data.ready ? 'alert-success' : 'alert-error');
            var html = '<ul class="list-disc list-inside">';
            data.criteria.forEach(function (c) {
                html += '<li>' + (c.passed ? '✓' : '✗') + ' ' + c.label + '</li>';
            });
            html += '</ul>';
            box.innerHTML = html;
        });
}
</script>
@endpush
