{{-- spec/Video_Management_Technical_Specification.md §7 — lưới video + lightbox. `embed_url`/
     `thumbnail_url`/`watch_url` LUÔN dựng từ youtube_video_id đã validate ở Model (§4.1) — view
     này KHÔNG BAO GIỜ đọc $video->embed_code (raw input người quản trị dán vào), tránh render
     HTML không kiểm soát ra trang công khai (§0/§5.2). --}}
@extends('layouts.frontend')

@section('title', 'Thư viện Video')

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-6">Thư viện Video</h1>

    @if($videos->isEmpty())
        {{-- Empty state — tránh để trắng trang khi chưa có video active nào, dễ gây hiểu nhầm là trang lỗi. --}}
        <div class="text-center py-16 text-base-content/60">
            <p>Chưa có video nào được đăng.</p>
        </div>
    @else
    <div x-data="{ open: false, activeUrl: null, activeTitle: '' }">
        <div class="video-gallery grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($videos as $video)
            <button type="button"
                    @click="open = true; activeUrl = '{{ $video->embed_url }}'; activeTitle = @js($video->name)"
                    aria-haspopup="dialog"
                    class="video-card text-left rounded-lg overflow-hidden border border-base-300 hover:shadow-lg transition">
                {{-- src ban đầu LUÔN là bản an toàn (hqdefault) — script cuối trang tự dò và nâng lên
                     Full HD (thumbnail_hd_url/maxresdefault) nếu video có bản HD thật (§7.4).
                     onerror: thumbnail YouTube trả 404 nếu video bị gỡ/riêng tư (§5.3/§9) — thay bằng
                     ảnh placeholder tĩnh sẵn có thay vì để icon ảnh vỡ. --}}
                <img src="{{ $video->thumbnail_url }}" data-thumb-hd="{{ $video->thumbnail_hd_url }}"
                     alt="{{ $video->name }}" loading="lazy"
                     onerror="this.onerror=null; this.src='{{ asset('images/post-cover-placeholder.svg') }}';"
                     class="w-full aspect-video object-cover">
                <div class="p-4">
                    <h3 class="font-semibold">{{ $video->name }}</h3>
                    @if($video->description)
                    <p class="text-sm text-base-content/70 mt-1">{{ Str::limit($video->description, 120) }}</p>
                    @endif
                </div>
            </button>
            @endforeach
        </div>

        {{-- Modal đặt NGOÀI vòng lặp @foreach (chỉ 1 instance dùng chung) — x-data ở trên bọc cả
             lưới lẫn modal nên state open/activeUrl chia sẻ được giữa 2 khối. --}}
        <div x-show="open" x-cloak
             role="dialog" aria-modal="true" :aria-label="activeTitle"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
             @keydown.escape.window="open = false">
            <div class="absolute inset-0" @click="open = false"></div>
            <div class="relative w-full max-w-3xl aspect-video mx-4">
                <button type="button" @click="open = false" aria-label="Đóng video"
                        class="absolute -top-10 right-0 text-white text-2xl leading-none">&times;</button>
                <template x-if="open">
                    <iframe :src="activeUrl + '?autoplay=1'" :title="activeTitle"
                            class="w-full h-full rounded-lg"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </template>
            </div>
        </div>
    </div>

    <div class="mt-8">{{ $videos->links() }}</div>
    @endif
</div>

<script>
/**
 * Nâng ảnh đại diện video lên Full HD (maxresdefault.jpg) khi có thật — bản inline riêng cho
 * trang công khai (không load bundle Modules/Video/resources/assets/js/video.js vì trang này
 * không cần Tabulator). Xem giải thích đầy đủ (vì sao phải "dò" bằng Image() tách biệt thay vì
 * onload/onerror trực tiếp trên ảnh hiển thị) tại video.js — cùng logic, giữ 2 bản riêng vì
 * trang public không có build step JS riêng cho module này.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img[data-thumb-hd]').forEach((img) => {
        const hdUrl = img.dataset.thumbHd;
        const probe = new Image();
        probe.onload = () => {
            if (probe.naturalWidth > 120) img.src = hdUrl;
        };
        probe.src = hdUrl;
    });
});
</script>
@endsection
