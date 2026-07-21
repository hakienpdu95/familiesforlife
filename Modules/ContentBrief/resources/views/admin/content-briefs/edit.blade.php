@extends('layouts.backend')
@section('title', 'Sửa Content Brief')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa Content Brief</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $brief->title }}
            @if($brief->currentVersion)
            <span class="badge {{ $brief->currentVersion->status->badgeClass() }} badge-sm ml-1.5">v{{ $brief->currentVersion->version_number }} — {{ $brief->currentVersion->status->label() }}</span>
            @endif
        </p>
    </div>
    <a href="{{ route('backend.content_brief.items.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

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

{{-- ── Thanh hành động theo trạng thái (§4.2) ──────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body py-3 flex-row flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-2">Hành động</span>

        @if($brief->currentVersion?->status?->value === 'draft')
        <form method="POST" action="{{ route('backend.content_brief.items.submit', $brief) }}">
            @csrf
            <button class="btn btn-sm btn-primary">Gửi duyệt</button>
        </form>
        @endif

        @can('approve', $brief)
            @if($brief->currentVersion?->status?->value === 'in_review')
            <form method="POST" action="{{ route('backend.content_brief.items.approve', $brief) }}">
                @csrf
                <button class="btn btn-sm btn-success">Duyệt</button>
            </form>
            <button type="button" class="btn btn-sm btn-error btn-outline" onclick="reject_modal.showModal()">Từ chối</button>
            @endif
        @endcan

        @if($brief->currentVersion?->status?->value === 'approved')
        <form method="POST" action="{{ route('backend.content_brief.items.generate', $brief) }}">
            @csrf
            <button class="btn btn-sm btn-secondary">Yêu cầu sinh nội dung</button>
        </form>
        @endif

        @if(in_array($brief->status?->value, ['draft', 'approved', 'in_review']))
        <form method="POST" action="{{ route('backend.content_brief.items.archive', $brief) }}"
              onsubmit="return confirm('Lưu trữ brief này?');">
            @csrf
            <button class="btn btn-sm btn-ghost">Lưu trữ</button>
        </form>
        @endif
    </div>
</div>

<dialog id="reject_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-3">Từ chối brief</h3>
        <form method="POST" action="{{ route('backend.content_brief.items.reject', $brief) }}">
            @csrf
            <textarea name="reason" required rows="3" class="textarea textarea-bordered w-full" placeholder="Lý do từ chối..."></textarea>
            <div class="modal-action">
                <button type="button" class="btn btn-ghost btn-sm" onclick="reject_modal.close()">Huỷ</button>
                <button type="submit" class="btn btn-error btn-sm">Xác nhận từ chối</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<form method="POST" action="{{ route('backend.content_brief.items.update', $brief) }}" novalidate>
    @csrf
    @method('PUT')
    @include('contentbrief::admin.content-briefs._form', ['brief' => $brief])
</form>

@endsection
