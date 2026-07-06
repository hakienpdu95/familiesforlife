@extends('layouts.backend')
@section('title', 'Version Manager — ' . $blueprint->name)

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

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $blueprint->name }} — Version Manager</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Mỗi version chỉ Clone được, không sửa trực tiếp sau khi published/deprecated/archived.</p>
        </div>
        <a href="{{ route('business_blueprint.admin.index') }}" class="btn btn-ghost btn-sm">← Danh sách Blueprint</a>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Version</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Published</th>
                        <th class="text-center">Org Solutions dùng</th>
                        <th class="w-64"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($versions as $version)
                <tr class="hover">
                    <td class="font-mono text-sm">{{ $version->version }}</td>
                    <td class="text-center">
                        @php
                            $badge = match($version->status) {
                                'published'  => 'badge-success',
                                'deprecated' => 'badge-warning',
                                'archived'   => 'badge-ghost',
                                default      => 'badge-outline',
                            };
                        @endphp
                        <span class="badge {{ $badge }} badge-sm">{{ $version->status }}</span>
                    </td>
                    <td class="text-xs text-base-content/60">{{ $version->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-center">{{ $version->organization_solutions_count }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            <a href="{{ route('business_blueprint.admin.versions.tree', [$blueprint, $version]) }}"
                               class="btn btn-ghost btn-xs">Xem cây</a>

                            @can(\App\Enums\PermissionEnum::BLUEPRINT_CLONE->value)
                            <form method="POST" action="{{ route('business_blueprint.admin.versions.clone', [$blueprint, $version]) }}">
                                @csrf
                                <input type="hidden" name="level" value="minor">
                                <button type="submit" class="btn btn-ghost btn-xs">Clone</button>
                            </form>
                            @endcan

                            @if ($version->status === 'archived')
                                {{-- immutable, chỉ Clone --}}
                            @elseif ($version->status !== 'published' && $version->status !== 'deprecated')
                                @can(\App\Enums\PermissionEnum::BLUEPRINT_ARCHIVE->value)
                                <form method="POST" action="{{ route('business_blueprint.admin.versions.archive', [$blueprint, $version]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-xs text-warning">Lưu trữ</button>
                                </form>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-base-content/40">Chưa có version nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
