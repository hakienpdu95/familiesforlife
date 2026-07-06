@extends('layouts.backend')
@section('title', 'Deploy Snapshots — ' . $deployment->organizationSolution->name)

@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $deployment->organizationSolution->name }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Snapshot tại thời điểm deploy — không lưu JSON, chỉ ghi lại tham chiếu (blueprint_version bất biến / danh sách config đang hiệu lực).
            </p>
        </div>
        <a href="{{ route('deployments.logs', $deployment) }}" class="btn btn-ghost btn-sm">← Xem log</a>
    </div>

    <div class="grid grid-cols-1 gap-4">
        @forelse ($deployment->snapshots as $snapshot)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">
                    {{ $snapshot->snapshot_type }}
                    <span class="text-xs text-base-content/40 font-normal ml-1">{{ $snapshot->created_at?->format('d/m/Y H:i:s') }}</span>
                </h2>

                @if ($snapshot->snapshot_type === 'blueprint')
                <p class="text-sm text-base-content/70">
                    Pin cứng vào Blueprint Version <span class="font-mono">v{{ $snapshot->blueprintVersion?->version }}</span>
                    (đã published — bất biến, không cần copy dữ liệu).
                </p>
                @else
                <table class="table table-sm">
                    <thead class="text-xs uppercase text-base-content/50">
                        <tr><th>Loại config</th><th>ID</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($snapshot->configItems as $item)
                        <tr>
                            <td class="font-mono text-xs">{{ $item->configurable_type }}</td>
                            <td>{{ $item->configurable_id }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-base-content/40">Không có config item nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @endif
            </div>
        </div>
        @empty
        <p class="text-sm text-base-content/40">Chưa có snapshot nào.</p>
        @endforelse
    </div>
</div>
@endsection
