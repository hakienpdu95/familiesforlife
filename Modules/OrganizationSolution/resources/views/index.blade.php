@extends('layouts.backend')
@section('title', 'Solution của tổ chức')

@section('content')
<div x-data="{ showActivate: false }">
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

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Solution của tổ chức</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Kích hoạt Business Solution cho tổ chức của bạn — không sửa Blueprint gốc, chỉ cấu hình overlay riêng.
            </p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" @click="showActivate = true">+ Kích hoạt Solution</button>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Solution</th>
                        <th>Blueprint version</th>
                        <th>Chủ sở hữu</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-48"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($organizationSolutions as $orgSolution)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $orgSolution->name }}</span>
                        <div class="text-xs text-base-content/40">{{ $orgSolution->businessSolution?->name }}</div>
                    </td>
                    <td class="font-mono text-xs">{{ $orgSolution->blueprintVersion?->version }}</td>
                    <td class="text-sm text-base-content/60">{{ $orgSolution->owner?->name }}</td>
                    <td class="text-center">
                        @php
                            $badge = match($orgSolution->status) {
                                'running'   => 'badge-success',
                                'ready'     => 'badge-info',
                                'suspended' => 'badge-warning',
                                'archived'  => 'badge-ghost',
                                default     => 'badge-outline',
                            };
                        @endphp
                        <span class="badge {{ $badge }} badge-sm">{{ $orgSolution->status }}</span>
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            <a href="{{ route('organization_solutions.wizard.capabilities.form', $orgSolution) }}" class="btn btn-ghost btn-xs">Cấu hình</a>
                            @can(\App\Enums\PermissionEnum::DEPLOYMENT_RUN->value)
                            @if ($orgSolution->status === 'ready')
                            <form method="POST" action="{{ route('deployments.deploy', $orgSolution) }}">
                                @csrf
                                <button class="btn btn-primary btn-xs">Deploy</button>
                            </form>
                            @endif
                            @endcan
                            @can(\App\Enums\PermissionEnum::DEPLOYMENT_VIEW_LOGS->value)
                            @if ($orgSolution->deployments->isNotEmpty())
                            <a href="{{ route('deployments.logs', $orgSolution->deployments->first()) }}" class="btn btn-ghost btn-xs">Xem log deploy</a>
                            @endif
                            @endcan
                            @if (!in_array($orgSolution->status, ['suspended', 'archived']))
                            <form method="POST" action="{{ route('organization_solutions.suspend', $orgSolution) }}">
                                @csrf
                                <button class="btn btn-ghost btn-xs text-warning">Tạm ngưng</button>
                            </form>
                            @endif
                            @if ($orgSolution->status !== 'archived')
                            <form method="POST" action="{{ route('organization_solutions.archive', $orgSolution) }}">
                                @csrf
                                <button class="btn btn-ghost btn-xs text-error">Lưu trữ</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-base-content/40">Chưa kích hoạt Business Solution nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal kích hoạt --}}
    <div x-cloak class="modal" :class="{ 'modal-open': showActivate }">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-base mb-4">Kích hoạt Business Solution</h3>
            <form method="POST" action="{{ route('organization_solutions.activate') }}">
                @csrf
                <div class="form-control mb-3">
                    <label class="label label-text text-xs">Business Solution <span class="text-error">*</span></label>
                    <select name="business_solution_id" id="activate-solution" class="select select-bordered select-sm" required
                            onchange="document.querySelectorAll('.version-option').forEach(o => { o.classList.add('hidden'); o.disabled = true; }); var t = document.getElementById('versions-'+this.value); if (t) { t.classList.remove('hidden'); t.disabled = false; }">
                        <option value="">— Chọn Business Solution —</option>
                        @foreach($publishedSolutions as $solution)
                        <option value="{{ $solution->id }}">{{ $solution->name }} ({{ $solution->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control mb-3">
                    <label class="label label-text text-xs">Blueprint Version (published) <span class="text-error">*</span></label>
                    @foreach($publishedSolutions as $solution)
                    <select name="blueprint_version_id" id="versions-{{ $solution->id }}"
                            class="select select-bordered select-sm version-option {{ $loop->first ? '' : 'hidden' }}"
                            @if(!$loop->first) disabled @endif required>
                        <option value="">— Chọn version —</option>
                        @foreach($solution->publishedBlueprintVersions as $version)
                        <option value="{{ $version->id }}">{{ $version->version }}</option>
                        @endforeach
                    </select>
                    @endforeach
                </div>

                <div class="form-control mb-4">
                    <label class="label label-text text-xs">Tên riêng trong tổ chức <span class="text-error">*</span></label>
                    <input type="text" name="name" placeholder="VD: AI Truy xuất HTX Tiên Dương" class="input input-bordered input-sm" required>
                </div>

                <div class="modal-action gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" @click="showActivate = false">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm">Kích hoạt</button>
                </div>
            </form>
        </div>
        <div @click="showActivate = false" class="modal-backdrop"></div>
    </div>
</div>
@endsection
