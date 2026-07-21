@extends('layouts.backend')
@section('title', 'Content Brief')

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

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Content Brief</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Gói nghiên cứu + chỉ dẫn viết bài — lớp kiểm soát đầu vào trước khi sinh nội dung</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\ContentBrief\Models\ContentBrief::class)
            <a href="{{ route('backend.content_brief.items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm brief
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach(\Modules\ContentBrief\Enums\BriefVersionStatus::cases() as $status)
            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tiêu đề/từ khoá..."
               class="input input-bordered input-sm w-56">
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('status'))
        <a href="{{ route('backend.content_brief.items.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Từ khoá mục tiêu</th>
                        <th>Phụ trách</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Version</th>
                        <th class="text-center">Cập nhật</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($briefs as $brief)
                    <tr>
                        <td class="font-medium">{{ $brief->title }}</td>
                        <td class="text-xs text-base-content/60">{{ $brief->target_keyword }}</td>
                        <td class="text-xs text-base-content/60">{{ $brief->assignee?->name ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $brief->status->badgeClass() }} badge-sm">{{ $brief->status->label() }}</span>
                        </td>
                        <td class="text-center text-xs">v{{ $brief->currentVersion?->version_number }} <span class="text-base-content/40">({{ $brief->versions_count }})</span></td>
                        <td class="text-center text-xs text-base-content/50">{{ $brief->updated_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('backend.content_brief.items.edit', $brief) }}" class="btn btn-ghost btn-xs">Sửa</a>
                                <form method="POST" action="{{ route('backend.content_brief.items.destroy', $brief) }}"
                                      onsubmit="return confirm('Xoá brief này?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-base-content/40">Chưa có brief nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $briefs->links() }}</div>
</div>
@endsection
