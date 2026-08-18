@extends('layouts.backend')
@section('title', 'Sửa playlist')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa playlist</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $playlist->name }} —
            <a href="{{ route('playlist.public.show', $playlist) }}" target="_blank" class="link">Xem trang công khai</a>
        </p>
    </div>
    <a href="{{ route('backend.playlist.items.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.playlist.items.update', $playlist) }}" novalidate class="mb-8">
    @csrf
    @method('PUT')
    @include('playlist::admin.playlists._form', ['playlist' => $playlist])
</form>

{{-- ── Gợi ý tiêu đề/mô tả/từ khoá qua AI (spec §10, thêm) ───────────────────────────────
     Sinh prompt COPY-PASTE sang 1 công cụ AI CÓ tìm kiếm web (Perplexity/ChatGPT Search/Claude
     Web Search...) — module này không gọi app/Services/AI/, cùng kiến trúc "copy-paste, người
     dùng tự soát lại" đã dùng ở AIVideoStudioTemplate/VideoIdeaExtractor. Prompt đã tính sẵn
     từ nội dung THẬT đang có trong playlist (đọc lại ở BuildPlaylistIdeaPromptAction), không cần
     người dùng tự gõ ngữ cảnh. --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-8" x-data="{ copied: false }">
    <div class="card-body">
        <h2 class="card-title text-base mb-1">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            Gợi ý tiêu đề, mô tả &amp; từ khoá bằng AI
        </h2>
        <p class="text-xs text-base-content/40 mb-3">
            Copy prompt bên dưới và dán sang 1 công cụ AI có khả năng tìm kiếm web (Perplexity, ChatGPT có Search, Claude có Web Search...). Prompt đã kèm sẵn tên/mô tả hiện tại và {{ $playlist->items->count() }} nội dung đang có trong playlist — đọc kỹ và tự chỉnh sửa kết quả trước khi dùng, không dán thẳng vào form phía trên.
        </p>
        <div class="flex items-center justify-end mb-1.5">
            <button type="button" class="btn btn-primary btn-xs gap-1.5"
                    @click="navigator.clipboard.writeText($refs.playlistIdeaPrompt.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="!copied">Copy</span>
                <span x-show="copied">Đã copy!</span>
            </button>
        </div>
        <textarea x-ref="playlistIdeaPrompt" readonly rows="10"
                  class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed">{{ $ideaPrompt }}</textarea>
    </div>
</div>

{{-- ── Quản lý nội dung trong playlist (spec §6.7/§5.1/§5.2) ────────────────────────────── --}}
<div x-data="playlistItemsManager({{ Js::from([
    'playlistId'  => $playlist->id,
    'items'       => $playlist->items->map(fn ($item) => [
        'id'         => $item->id,
        'title'      => $item->resolved_itemable?->getPlaylistCardTitle() ?? '(Nội dung đã bị xoá)',
        'typeLabel'  => $item->resolved_itemable?->getPlaylistCardTypeLabel() ?? '—',
        'sortOrder'  => $item->sort_order,
        // badge cảnh báo item ẩn/mồ côi (§6.7) — null resolved = nguồn đã xoá cứng, khác false
        // isPlaylistCardVisible() = còn tồn tại nhưng đang ẩn/chưa publish.
        'warning'    => $item->resolved_itemable === null
            ? 'Nguồn đã xoá'
            : (! $item->visible_itemable ? 'Đang ẩn' : null),
    ])->values(),
    'searchUrl'   => route('backend.api.playlists.searchable-items', $playlist),
    'attachUrl'   => route('backend.playlist.items.attach-item', $playlist),
    'reorderUrl'  => route('backend.playlist.items.reorder-items', $playlist),
    'detachUrlTemplate' => route('backend.playlist.items.detach-item', ['playlistItem' => '__ID__']),
]) }})">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">

            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="card-title text-base">
                    Nội dung trong playlist
                    <span class="badge badge-ghost badge-sm" x-text="items.length"></span>
                </h2>
                <div class="flex items-center gap-2">
                    <button type="button" @click="saveOrder()" x-show="items.length > 1"
                            class="btn btn-ghost btn-sm gap-1.5" :disabled="savingOrder">
                        <span x-show="!savingOrder">Lưu thứ tự</span>
                        <span x-show="savingOrder">Đang lưu...</span>
                    </button>
                    <button type="button" @click="openPicker()" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Thêm nội dung
                    </button>
                </div>
            </div>

            <template x-if="items.length === 0">
                <div class="py-10 text-center text-sm text-base-content/40">
                    Playlist chưa có nội dung nào — bấm "Thêm nội dung" để bắt đầu.
                </div>
            </template>

            <div class="overflow-x-auto" x-show="items.length > 0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-20">Thứ tự</th>
                            <th>Tên</th>
                            <th class="w-28">Loại</th>
                            <th class="w-32">Trạng thái</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td>
                                    <input type="number" min="1" x-model.number="item.sortOrder"
                                           class="input input-bordered input-xs w-16">
                                </td>
                                <td class="text-sm" x-text="item.title"></td>
                                <td><span class="badge badge-sm" x-text="item.typeLabel"></span></td>
                                <td>
                                    <span class="badge badge-warning badge-sm" x-show="item.warning" x-text="item.warning"></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-ghost btn-xs text-error" @click="removeItem(item.id)">Gỡ</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ── Modal tìm kiếm hợp nhất (spec §0/§6.4) ─────────────────────────────────────────── --}}
    <dialog id="playlistItemPickerModal" class="modal" x-ref="pickerModal">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-1">Thêm nội dung vào playlist</h3>
            <p class="text-xs text-base-content/50 mb-3">Tìm theo tên video hoặc tiêu đề bài viết — kết quả trộn cả 2 loại.</p>

            {{-- CHỈ x-model.debounce — KHÔNG kèm @input gọi doSearch() trực tiếp (cùng lỗi đã
                 ghi chú ở Modules/Video/resources/assets/js/pages/video-index.js): x-model
                 debounce trì hoãn gán giá trị vào `search`, nhưng @input bắn NGAY mỗi phím gõ
                 nên đọc `this.search` khi còn CHƯA kịp cập nhật — luôn tìm bằng giá trị cũ/rỗng.
                 Kích hoạt tìm kiếm qua $watch('search', ...) trong init() (playlist-edit.js) —
                 chỉ 1 đường duy nhất, chạy SAU khi giá trị đã debounce xong. --}}
            <input type="text" x-model.debounce.400ms="search"
                   placeholder="Nhập từ khoá (tối thiểu 2 ký tự)..."
                   class="input input-bordered input-sm w-full mb-3">

            <div class="min-h-[120px] max-h-80 overflow-y-auto space-y-1">
                <template x-if="searching">
                    <p class="text-xs text-base-content/40 text-center py-6">Đang tìm...</p>
                </template>
                <template x-if="!searching && search.length >= 2 && results.length === 0">
                    <p class="text-xs text-base-content/40 text-center py-6">Không tìm thấy nội dung phù hợp.</p>
                </template>
                <template x-for="candidate in results" :key="candidate.type + ':' + candidate.id">
                    <div class="flex items-center gap-2.5 p-2 rounded hover:bg-base-200">
                        <img :src="candidate.thumbnail_url || '{{ asset('images/post-cover-placeholder.svg') }}'"
                             class="w-14 h-9 object-cover rounded shrink-0" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm truncate" x-text="candidate.title"></p>
                            <span class="badge badge-ghost badge-xs" x-text="candidate.type_label"></span>
                        </div>
                        <button type="button" class="btn btn-primary btn-xs shrink-0"
                                :disabled="addingKey === (candidate.type + ':' + candidate.id)"
                                @click="addItem(candidate)">Thêm</button>
                    </div>
                </template>
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-ghost btn-sm" @click="closePicker()">Đóng</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

</div>

@endsection

@push('scripts')
    @vite(['Modules/Playlist/resources/assets/js/playlist.js'], 'build/backend')
@endpush
