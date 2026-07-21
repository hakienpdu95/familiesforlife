@extends('layouts.backend')
@section('title', 'Trang tĩnh')

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

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Trang tĩnh</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Giới thiệu, Liên hệ, Điều khoản, Chính sách... — không tổ chức hoá, URL gốc</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\Page\Models\Page::class)
            <a href="{{ route('backend.page.items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm trang
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach(\Modules\Page\Enums\PageStatus::cases() as $status)
            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tiêu đề/đường dẫn..."
               class="input input-bordered input-sm w-56">
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('status'))
        <a href="{{ route('backend.page.items.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Đường dẫn</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Cập nhật</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td class="font-medium">
                            {{ $page->title }}
                            @if($page->is_system)
                            <span class="badge badge-ghost badge-xs ml-1.5" title="Trang hệ thống — không thể xoá">Hệ thống</span>
                            @endif
                        </td>
                        <td class="text-xs text-base-content/50">/{{ $page->slug }}</td>
                        <td class="text-center">
                            @if($page->status === \Modules\Page\Enums\PageStatus::Published)
                            <span class="badge badge-success badge-sm">{{ $page->status->label() }}</span>
                            @else
                            <span class="badge badge-ghost badge-sm">{{ $page->status->label() }}</span>
                            @endif
                        </td>
                        <td class="text-center text-xs text-base-content/50">{{ $page->updated_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @if($page->status === \Modules\Page\Enums\PageStatus::Published)
                                <form method="POST" action="{{ route('backend.page.items.unpublish', $page) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-ghost btn-xs" title="Chuyển về Nháp">Gỡ xuất bản</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('backend.page.items.publish', $page) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-ghost btn-xs text-success" title="Xuất bản">Xuất bản</button>
                                </form>
                                @endif
                                <a href="{{ route('backend.page.items.edit', $page) }}" class="btn btn-ghost btn-xs">Sửa</a>
                                @unless($page->is_system)
                                <form method="POST" action="{{ route('backend.page.items.destroy', $page) }}"
                                      onsubmit="return confirm('Xoá trang này?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                                </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-base-content/40">Chưa có trang tĩnh nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $pages->links() }}</div>
</div>
@endsection
