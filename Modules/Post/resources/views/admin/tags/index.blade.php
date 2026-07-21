@extends('layouts.backend')
@section('title', 'Quản lý tag')

@section('content')
<div x-data="{ mergeOpen: false, mergeAction: '', mergeSourceName: '', mergeSourceId: null }">

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
            <h1 class="text-2xl font-bold text-base-content">Quản lý tag</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Nhãn gắn vào bài viết — đổi tên, gộp, xoá tập trung</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm">← Bài viết</a>
            @can('create', \Modules\Post\Models\PostTag::class)
            <a href="{{ route('backend.post.tags.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm tag
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên tag..."
               class="input input-bordered input-sm w-56">
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q'))
        <a href="{{ route('backend.post.tags.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tên tag</th>
                        <th class="text-center">Số bài viết</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tags as $tag)
                    <tr class="hover">
                        <td>
                            <span class="font-medium text-sm">{{ $tag->name }}</span>
                            <div class="text-xs text-base-content/40 font-mono">{{ $tag->slug }}</div>
                        </td>
                        <td class="text-center text-sm">{{ $tag->articles_count }}</td>
                        <td>
                            <div class="flex gap-1">
                                @can('update', $tag)
                                <a href="{{ route('backend.post.tags.edit', $tag) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endcan
                                @can('delete', $tag)
                                @if($tags->count() > 1)
                                <button type="button" class="btn btn-ghost btn-xs btn-square" title="Gộp vào tag khác"
                                        @click="mergeOpen = true; mergeAction = '{{ route('backend.post.tags.merge', $tag) }}'; mergeSourceName = @js($tag->name); mergeSourceId = {{ $tag->id }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </button>
                                @endif
                                <form method="POST" action="{{ route('backend.post.tags.destroy', $tag) }}"
                                      onsubmit="return confirm('Xoá tag &quot;{{ $tag->name }}&quot;?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-8 text-base-content/40">
                            @if($search)
                                Không tìm thấy tag nào khớp "{{ $search }}".
                            @else
                                Chưa có tag nào. Tag được tạo tự động khi nhập ở form bài viết, hoặc bấm "Thêm tag" ở trên.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Modal gộp tag — dùng chung 1 modal cho mọi hàng, đổi action/tên qua Alpine
         (spec/PostTag_Management_Technical_Specification.md §3.2: chốt modal ở trang danh
         sách, không làm trang chi tiết riêng) ── --}}
    <div class="modal" :class="mergeOpen ? 'modal-open' : ''" x-cloak>
        <div class="modal-box">
            <h3 class="font-bold text-lg">Gộp tag</h3>
            <p class="text-sm text-base-content/60 mt-1">
                Gộp <span class="font-medium" x-text="'\"' + mergeSourceName + '\"'"></span> vào tag đích —
                mọi bài viết đang dùng tag này sẽ chuyển sang tag đích, tag nguồn sẽ bị xoá.
            </p>
            <form method="POST" :action="mergeAction" class="mt-4">
                @csrf
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Gộp vào tag <span class="text-error">*</span></span></label>
                    <select name="target_tag_id" required class="select select-bordered select-sm w-full">
                        <option value="">— Chọn tag đích —</option>
                        @foreach($tags as $t)
                        <option value="{{ $t->id }}" x-bind:disabled="mergeSourceId === {{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost btn-sm" @click="mergeOpen = false">Huỷ</button>
                    <button type="submit" class="btn btn-primary btn-sm">Gộp tag</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="mergeOpen = false"></div>
    </div>
</div>
@endsection
