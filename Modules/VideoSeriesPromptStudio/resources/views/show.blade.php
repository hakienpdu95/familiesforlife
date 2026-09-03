@extends('layouts.backend')
@section('title', $prompt->label)

@section('content')
<div x-data="videoSeriesPromptShowPage()">

    @foreach (['success', 'error'] as $type)
        @if (session($type))
            <div class="alert alert-{{ $type }} text-sm mb-4">{{ session($type) }}</div>
        @endif
    @endforeach

    <div class="mb-5 flex items-start justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $prompt->label }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Chủ đề: {{ $prompt->series_topic }}
                — {{ $prompt->platformLabel() }}
                @if ($prompt->pov) — Góc nhìn: {{ $prompt->pov }} @endif
                @if ($prompt->category) — Chuyên mục: {{ $prompt->category->name }} @endif
                — {{ $prompt->episode_count }} tập
            </p>
        </div>
        <a href="{{ route('backend.videoseriespromptstudio.index') }}" class="btn btn-ghost btn-xs">← Danh sách</a>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <div>
                    <h2 class="card-title text-base">Nội dung yêu cầu (prompt) hoàn chỉnh</h2>
                    <p class="text-xs text-base-content/40">Sao chép toàn bộ đoạn bên dưới, dán vào ChatGPT, Claude, hoặc công cụ AI bạn đang dùng</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm gap-1.5" @click="copyPrompt()">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span x-show="!copied">Copy Prompt</span>
                    <span x-show="copied" x-cloak>Đã copy!</span>
                </button>
            </div>
            <textarea id="video-series-prompt-rendered" readonly rows="20"
                      class="textarea textarea-bordered w-full h-96 font-mono text-sm">{{ $prompt->rendered_prompt }}</textarea>
        </div>
    </div>

    <form action="{{ route('backend.videoseriespromptstudio.destroy', $prompt) }}" method="POST"
          onsubmit="return confirm('Xoá prompt này? Không thể khôi phục.');" class="mt-4">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-ghost btn-sm text-error">Xoá prompt này</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('videoSeriesPromptShowPage', () => ({
        copied: false,

        async copyPrompt() {
            const el = document.getElementById('video-series-prompt-rendered');
            if (!el) return;

            await navigator.clipboard.writeText(el.value);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        },
    }));
});
</script>
@endpush
