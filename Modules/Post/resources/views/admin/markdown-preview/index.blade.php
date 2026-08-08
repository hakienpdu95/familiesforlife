@extends('layouts.backend')
@section('title', 'Xem trước Markdown')

@section('content')
<div class="space-y-6">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content">Xem trước Markdown</h1>
            <p class="text-sm text-base-content/50 mt-0.5 max-w-2xl">
                Xem đúng bản Markdown mà content negotiation trả về khi crawler/agent gửi header
                <code class="bg-base-200 px-1.5 py-0.5 rounded text-xs">Accept: text/markdown</code>
                khi tải URL bài viết (cùng 1 URL với bản HTML — không có URL riêng cho bản Markdown).
            </p>
        </div>
    </div>

    {{-- ── Article picker ────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('backend.post.markdown-preview.index') }}">
                <label class="label" for="markdown-preview-picker">
                    <span class="label-text font-medium">Chọn bài viết đã xuất bản</span>
                </label>
                <select id="markdown-preview-picker" name="translation_id"
                        data-ts-remote-url="{{ route('backend.api.post.articles.search') }}"
                        class="w-full">
                    <option value=""></option>
                    @if($translation)
                    <option value="{{ $translation->id }}" selected>{{ $translation->title }}</option>
                    @endif
                </select>
            </form>
        </div>
    </div>

    {{-- ── Preview ──────────────────────────────────────────────────── --}}
    @if($translation)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <h2 class="font-semibold text-base-content truncate">{{ $translation->title }}</h2>
                    <a href="{{ $canonicalUrl }}" target="_blank" rel="noopener"
                       class="text-xs text-primary hover:underline break-all">{{ $canonicalUrl }} ↗</a>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge badge-ghost badge-sm font-mono">Content-Type: text/markdown</span>
                    <button type="button" id="markdown-copy-btn" class="btn btn-ghost btn-sm gap-1.5"
                            data-clipboard-target="markdown-source">
                        Sao chép
                    </button>
                </div>
            </div>

            <pre id="markdown-source" class="bg-base-200 rounded-xl p-4 text-xs leading-relaxed overflow-x-auto whitespace-pre-wrap max-h-[70vh]">{{ $markdown }}</pre>
        </div>
    </div>
    @else
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-8 text-center text-sm text-base-content/40 italic">
            Chọn 1 bài viết ở trên để xem trước bản Markdown.
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/modules/tom-select.js', 'Modules/Post/resources/assets/js/post.js'], 'build/backend')
    <script>
    document.getElementById('markdown-copy-btn')?.addEventListener('click', function () {
        const source = document.getElementById('markdown-source');
        if (!source) return;

        navigator.clipboard.writeText(source.textContent).then(() => {
            const label = this.textContent;
            this.textContent = 'Đã sao chép ✓';
            setTimeout(() => { this.textContent = label; }, 1500);
        });
    });
    </script>
@endpush
