@extends('layouts.backend')
@section('title', $prompt->label)

@section('content')

@foreach(['success','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
    </div>
    @endif
@endforeach

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $prompt->label }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex flex-wrap items-center gap-1.5">
            <span>Mẫu đã dùng:</span>
            @if($framework)
                <span class="badge badge-primary badge-outline font-mono">{{ $framework['name'] }}</span>
            @else
                <span class="badge badge-warning font-mono">{{ $prompt->framework_key }} — đã gỡ</span>
            @endif

            {{-- §4.4 (v2.7) — nêu rõ prompt này đã được đắp ngữ cảnh biên tập của chuyên mục nào:
                 đó là phần nội dung người dùng KHÔNG tự gõ, nên phải nhìn thấy được nó từ đâu ra. --}}
            @if($prompt->category)
                <span class="badge badge-ghost gap-1" title="Prompt này có chèn ngữ cảnh biên tập của chuyên mục">
                    Ngữ cảnh: {{ $prompt->category->name }}
                </span>
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        {{-- (2026-08-28, phản hồi review) — chỉ framework `topiccluster` có khái niệm Pillar/Cluster
             để dán kết quả AI về duyệt/đẩy sang Content Outlines, xem TopicClusterResultController. --}}
        @if($prompt->framework_key === 'topiccluster')
        <a href="{{ route('backend.promptstudio.prompts.topic-cluster-result.show', $prompt) }}" class="btn btn-secondary btn-outline btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Dán kết quả AI &amp; đẩy sang Content Outlines
        </a>
        @endif
        @if($framework)
        <a href="{{ route('backend.promptstudio.prompts.edit', $prompt) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Sửa &amp; Sinh lại
        </a>
        @endif
        <a href="{{ route('backend.promptstudio.prompts.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

{{-- spec §5.4 — orphaned: framework đã bị gỡ khỏi config, không thể sửa/sinh lại. --}}
@unless($framework)
<div class="alert py-3 px-4 mb-4 bg-warning/10 border border-warning/40 text-sm">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span>Framework "<b>{{ $prompt->framework_key }}</b>" đã bị gỡ khỏi hệ thống — không thể sửa hoặc sinh lại. Bạn vẫn có thể xem và sao chép nội dung đã lưu bên dưới, hoặc xoá bản ghi này.</span>
</div>
@endunless

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <div>
                <h2 class="card-title text-base">Nội dung yêu cầu (prompt) hoàn chỉnh</h2>
                <p class="text-xs text-base-content/40">Sao chép toàn bộ đoạn bên dưới, dán vào ChatGPT, Claude, hoặc công cụ AI bạn đang dùng</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm gap-1.5"
                    onclick="window.promptStudioCopyPrompt('prompt-studio-rendered', this)">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Sao chép prompt</span>
            </button>
        </div>
        <textarea id="prompt-studio-rendered" readonly rows="14"
                  class="textarea textarea-bordered w-full font-mono text-sm">{{ $prompt->rendered_prompt }}</textarea>
    </div>
</div>

<form method="POST" action="{{ route('backend.promptstudio.prompts.destroy', $prompt) }}"
      onsubmit="return confirm('Xoá prompt \'{{ $prompt->label }}\'? Không thể khôi phục.');"
      class="mt-4">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-ghost btn-sm text-error">Xoá prompt này</button>
</form>

@endsection

@push('scripts')
    @vite(['Modules/PromptFrameworkStudio/resources/assets/js/prompt-framework-studio.js'], 'build/backend')
@endpush
