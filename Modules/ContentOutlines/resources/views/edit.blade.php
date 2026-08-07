@extends('layouts.backend')
@section('title', 'Sửa dàn ý nội dung')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa dàn ý: {{ $outline->label }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Sinh lại prompt sẽ GHI ĐÈ prompt cũ — không giữ lịch sử các lần sửa trước</p>
    </div>
    <a href="{{ route('backend.contentoutlines.show', $outline) }}" class="btn btn-ghost btn-sm gap-1.5">
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

@php
    // §4.24 (v1.20) — rủi ro "cascade khi regenerate outline": RegenerateContentOutlinePromptAction
    // CHỈ ghi đè generated_prompt (§4.2), KHÔNG đụng approved_outline/article_draft_prompt/
    // drafted_article/review_prompt — outline mới có thể không còn khớp với Bước 2/3 đã sinh
    // trước đó (viết dựa trên outline CŨ). Cảnh báo rõ trong confirm dialog khi Bước 2/3 đã có,
    // KHÔNG tự xoá/cảnh báo ngầm (người dùng có thể có lý do chính đáng giữ Bước 2/3 cũ).
    $hasDownstream = filled($outline->article_draft_prompt) || filled($outline->review_prompt);
    $regenerateMessage = $hasDownstream
        ? 'Sinh lại sẽ GHI ĐÈ outline hiện tại — KHÔNG thể khôi phục (không versioning). Outline này ĐÃ có Bước 2 (viết bài)/Bước 3 (soát lỗi) — nội dung đó KHÔNG tự cập nhật theo outline mới và có thể không còn khớp. Tiếp tục?'
        : '1';
@endphp

{{-- §4.2 (v1.1) — data-confirm-regenerate đọc bởi content-outlines.js: xác nhận trước khi
     GHI ĐÈ generated_prompt (không thể khôi phục lại prompt cũ, khác create không cần confirm).
     §4.24 (v1.20) — message ĐỔI thành cảnh báo cascade khi Bước 2/3 đã có (xem @php ở trên). --}}
<form method="POST" action="{{ route('backend.contentoutlines.update', $outline) }}" data-confirm-regenerate="{{ $regenerateMessage }}" novalidate>
    @csrf
    @method('PUT')
    @include('contentoutlines::_form', ['outline' => $outline])
</form>

@endsection

@push('scripts')
    @vite(['Modules/ContentOutlines/resources/assets/js/content-outlines.js'], 'build/backend')
@endpush
