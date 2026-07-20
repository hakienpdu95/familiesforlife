@extends('layouts.backend')
@section('title', 'Soạn bản tin')

@section('content')
<div>

@foreach(['success','error'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition.opacity.duration.500ms class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
        <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
    </div>
    @endif
@endforeach

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Soạn bản tin</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Gửi tới toàn bộ danh sách đăng ký qua Resend Broadcast.</p>
    </div>
    <a href="{{ route('backend.newsletter.broadcast.logs') }}" class="btn btn-ghost btn-sm gap-1.5">
        Lịch sử gửi
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.newsletter.broadcast.send') }}" novalidate
      class="card bg-base-100 shadow-sm border border-base-200"
      x-data="{ showSchedule: false }">
    @csrf

    <div class="card-body p-6 space-y-4">

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Chủ đề (subject) <span class="text-error">*</span></span></label>
            <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255"
                   class="input input-bordered input-sm w-full @error('subject') input-error @enderror">
            @error('subject')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <div class="flex items-center justify-between mb-1">
                <label class="label py-0 !p-0"><span class="label-text font-medium">Nội dung <span class="text-error">*</span></span></label>
                <button type="button" class="btn btn-ghost btn-xs" onclick="window.__nlInsertUnsubscribeTag()">
                    + Chèn link unsubscribe (bắt buộc)
                </button>
            </div>
            <textarea name="body_html" id="body_html" class="jodit-editor" data-jodit-preset="standard">{{ old('body_html') }}</textarea>
            @error('body_html')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            <p class="text-xs text-base-content/40 mt-1">
                Nội dung bắt buộc phải chứa <code>@{{{RESEND_UNSUBSCRIBE_URL}}}</code> — Resend tự thay bằng link huỷ đăng ký hợp lệ cho từng người nhận.
            </p>
        </div>

        <div class="form-control">
            <label class="flex items-center gap-2 cursor-pointer select-none w-fit">
                <input type="checkbox" class="checkbox checkbox-sm" @change="showSchedule = $event.target.checked">
                <span class="text-sm font-medium">Lên lịch gửi (thay vì gửi ngay)</span>
            </label>
            <div x-show="showSchedule" x-cloak x-transition class="pt-2 max-w-xs">
                <input type="text" name="scheduled_at" id="fp-scheduled-at" value="{{ old('scheduled_at') }}"
                       class="input input-bordered input-sm w-full fp-init" data-fp-mode="datetime"
                       placeholder="DD/MM/YYYY HH:mm">
                @error('scheduled_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-base-200">
            <button type="submit" class="btn btn-primary btn-sm" x-bind:class="showSchedule ? '' : 'btn-success'"
                    onclick="return confirm('Gửi bản tin này tới toàn bộ danh sách đăng ký?')">
                <span x-text="showSchedule ? 'Lên lịch gửi' : 'Gửi ngay'"></span>
            </button>
        </div>

    </div>
</form>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/jodit.js',
        'resources/js/modules/flatpickr.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => window.initJoditAll?.('.jodit-editor'));

    window.__nlInsertUnsubscribeTag = function () {
        const editor = window.JoditInstances?.get('body_html');
        const tag = '<p>@{{{RESEND_UNSUBSCRIBE_URL}}}</p>';
        if (editor) {
            editor.value = (editor.value || '') + tag;
        } else {
            const textarea = document.getElementById('body_html');
            if (textarea) textarea.value = (textarea.value || '') + tag;
        }
    };
    </script>
@endpush
