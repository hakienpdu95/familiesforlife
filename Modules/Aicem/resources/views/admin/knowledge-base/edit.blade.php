@extends('layouts.backend')
@section('title', 'Sửa tri thức — Knowledge Base')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $document->title }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex items-center gap-2">
            <span class="badge badge-sm badge-ghost font-mono">{{ $document->type }}</span>
            <span>{{ $document->subject_type ?? 'DNA chung' }}</span>
            <span>· v{{ $document->current_version }}</span>
        </p>
    </div>
    <a href="{{ route('backend.aicem.knowledge-documents.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@foreach(['success', 'error'] as $type)
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

<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

    <div class="space-y-5">
        <form method="POST" action="{{ route('backend.aicem.knowledge-documents.update', $document) }}">
            @csrf
            @method('PUT')

            <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">Nội dung</h2>

                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title', $document->title) }}"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   maxlength="255">
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nội dung (Markdown) <span class="text-error">*</span></span>
                            </label>
                            <textarea name="content" rows="12"
                                      class="textarea textarea-bordered textarea-sm w-full font-mono @error('content') textarea-error @enderror">{{ old('content', $document->content) }}</textarea>
                            @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">Phạm vi áp dụng (scope)</h2>

                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Scope (JSON)</span>
                                <span class="label-text-alt text-xs text-base-content/40">Để trống = áp dụng mọi bài/sản phẩm</span>
                            </label>
                            @php
                                $docSchema = $taxonomySchema[$document->subject_type] ?? null;
                            @endphp
                            <textarea name="scope_json" rows="3"
                                      @disabled(! $docSchema)
                                      class="textarea textarea-bordered textarea-sm w-full font-mono @error('scope_json') textarea-error @enderror">{{ old('scope_json', $document->scope ? json_encode($document->scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                            @error('scope_json')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @if($docSchema)
                            <p class="text-xs text-base-content/40 mt-1">
                                Key phải khớp taxonomy_keys của <span class="font-mono">{{ $document->subject_type }}</span>:
                                <span class="font-mono">{{ implode(', ', $docSchema) }}</span>.
                            </p>
                            @else
                            <p class="text-xs text-warning mt-1">
                                Tài liệu này là DNA chung toàn tổ chức (không gắn subject_type) — KHÔNG được đặt scope, để trống ô này (đã tự khoá).
                            </p>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Scope match</span>
                                </label>
                                <select name="scope_match" class="select select-bordered select-sm w-full">
                                    <option value="any" {{ old('scope_match', $document->scope_match->value) === 'any' ? 'selected' : '' }}>any — khớp 1 key là đủ</option>
                                    <option value="all" {{ old('scope_match', $document->scope_match->value) === 'all' ? 'selected' : '' }}>all — phải khớp mọi key</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Priority</span>
                                </label>
                                <input type="number" name="priority" min="1" max="999"
                                       value="{{ old('priority', $document->priority) }}"
                                       class="input input-bordered input-sm w-full @error('priority') input-error @enderror">
                                @error('priority')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Lưu thay đổi
                </button>
            </div>
        </form>

        {{-- ── Lịch sử phiên bản / rollback ─────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-1">Lịch sử phiên bản</h2>
                <p class="text-xs text-base-content/40 mb-4">
                    Mỗi lần lưu tạo 1 version mới lưu lại trạng thái trước đó. Khôi phục sẽ tạo thêm 1 version mới
                    (không xoá lịch sử) — bản thân thao tác khôi phục cũng được audit lại được.
                </p>

                @forelse($document->versions as $version)
                <div class="flex items-start justify-between gap-3 py-3 {{ ! $loop->last ? 'border-b border-base-200' : '' }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="badge badge-sm badge-outline">v{{ $version->version }}</span>
                            <span class="text-xs text-base-content/50">{{ $version->changed_at?->format('d/m/Y H:i') }}</span>
                            <span class="text-xs text-base-content/40">bởi {{ $version->changer?->name ?? '—' }}</span>
                        </div>
                        <p class="text-xs text-base-content/60 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($version->content, 160) }}</p>
                    </div>
                    @can('rollback', $document)
                    <form method="POST" action="{{ route('backend.aicem.knowledge-documents.versions.restore', [$document, $version]) }}"
                          onsubmit="return confirm('Khôi phục về phiên bản v{{ $version->version }}? Trạng thái hiện tại sẽ được lưu thành 1 version mới trước khi ghi đè.')">
                        @csrf
                        <button class="btn btn-ghost btn-xs shrink-0">Khôi phục</button>
                    </form>
                    @endcan
                </div>
                @empty
                <p class="text-sm text-base-content/40 py-4 text-center">Chưa có lịch sử — tài liệu chưa từng được sửa.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 text-sm space-y-2">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Thông tin</p>
                <p><span class="text-base-content/50">Type:</span> <span class="font-mono">{{ $document->type }}</span></p>
                <p><span class="text-base-content/50">Subject:</span> {{ $document->subject_type ?? 'DNA chung' }}</p>
                <p><span class="text-base-content/50">Tạo bởi:</span> {{ $document->creator?->name ?? '—' }}</p>
                <p><span class="text-base-content/50">Sửa lần cuối bởi:</span> {{ $document->updater?->name ?? '—' }}</p>
                <p class="text-xs text-base-content/30 pt-2 border-t border-base-200">
                    Type/subject_type không thể sửa sau khi tạo — cần thiết thì xoá và tạo tài liệu mới.
                </p>
            </div>
        </div>
    </div>

</div>

@endsection
