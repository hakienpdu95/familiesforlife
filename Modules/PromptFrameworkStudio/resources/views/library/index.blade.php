@extends('layouts.backend')
@section('title', 'Thư viện framework')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-2">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thư viện mẫu câu lệnh cho AI</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Chọn 1 mẫu phù hợp bên dưới, hệ thống sẽ hướng dẫn bạn điền từng ô để tự tạo ra 1 yêu cầu (prompt) đầy đủ, gửi cho ChatGPT/Claude hoặc công cụ AI khác</p>
    </div>
    <a href="{{ route('backend.promptstudio.prompts.index') }}" class="btn btn-ghost btn-sm">Prompt đã tạo</a>
</div>

<div class="alert bg-info/10 border border-info/30 text-sm py-3 px-4 mb-5">
    <svg class="w-5 h-5 shrink-0 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>Không cần biết "prompt" hay "framework" là gì — mỗi mẫu dưới đây chỉ là 1 bộ câu hỏi có sẵn (giống tờ khai in sẵn). Bạn chỉ cần đọc mô tả <b>"Phù hợp khi..."</b> để chọn đúng mẫu, rồi điền vào từng ô theo ví dụ gợi ý.</span>
</div>

@foreach($groupedFrameworks as $groupName => $frameworks)
<div class="{{ $loop->first ? '' : 'mt-8' }} mb-3 flex items-center gap-2">
    <h2 class="text-base font-bold text-base-content">{{ $groupName }}</h2>
    <span class="badge badge-ghost badge-sm">{{ count($frameworks) }} mẫu</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($frameworks as $framework)
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:border-primary/40 hover:shadow-md transition-all">
        <div class="card-body p-5">
            <div class="flex items-start justify-between gap-2 mb-1">
                <span class="badge badge-primary badge-outline font-mono text-xs">{{ $framework['name'] }}</span>
                <span class="badge badge-ghost badge-sm">{{ count($framework['fields']) }} câu hỏi</span>
            </div>

            <p class="text-sm font-medium text-base-content mt-2">Phù hợp khi:</p>
            <p class="text-sm text-base-content/70">{{ $framework['best_for'] }}</p>

            <p class="text-xs text-base-content/40 mt-2">{{ $framework['description'] }}</p>

            <details class="mt-3 group">
                <summary class="cursor-pointer text-xs font-medium text-primary select-none flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Xem ví dụ kết quả
                </summary>
                <textarea readonly rows="6" class="textarea textarea-bordered textarea-xs w-full mt-2 font-mono bg-base-200/40">{{ $framework['rendered_example'] }}</textarea>
            </details>

            <a href="{{ route('backend.promptstudio.prompts.create', ['framework' => $framework['key']]) }}"
               class="btn btn-primary btn-sm btn-block mt-4 gap-1.5">
                Dùng mẫu này
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
    @endforeach
</div>
@endforeach

@endsection
