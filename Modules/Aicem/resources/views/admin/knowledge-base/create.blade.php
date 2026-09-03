@extends('layouts.backend')
@section('title', 'Thêm tri thức — Kho tri thức')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm tri thức</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Thêm 1 tài liệu vào Kho tri thức dùng làm ngữ cảnh cho AICEM</p>
    </div>
    <a href="{{ route('backend.aicem.knowledge-documents.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.aicem.knowledge-documents.store') }}"
      x-data="{ subjectType: '{{ old('subject_type', '') }}', taxonomy: @js($taxonomySchema) }">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">Loại tri thức</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Đối tượng áp dụng</span>
                            </label>
                            <select name="subject_type" x-model="subjectType"
                                    class="select select-bordered select-sm w-full @error('subject_type') select-error @enderror">
                                <option value="">— DNA chung toàn tổ chức (không gắn subject) —</option>
                                @foreach($subjectTypes as $st)
                                <option value="{{ $st['key'] }}" {{ old('subject_type') === $st['key'] ? 'selected' : '' }}>{{ $st['label'] }} ({{ $st['key'] }})</option>
                                @endforeach
                            </select>
                            @error('subject_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại <span class="text-error">*</span></span>
                            </label>
                            <select name="type" class="select select-bordered select-sm w-full @error('type') select-error @enderror">
                                <option value="">— Chọn loại tri thức —</option>
                                @foreach(config('aicem_subjects.knowledge_tier_labels') as $tierKey => $tierLabel)
                                @php $tierItems = collect($slotDefinitions)->where('tier', $tierKey); @endphp
                                @if($tierItems->isNotEmpty())
                                <optgroup label="{{ $tierLabel }}">
                                    @foreach($tierItems as $typeKey => $def)
                                    <option value="{{ $typeKey }}" {{ old('type') === $typeKey ? 'selected' : '' }}>
                                        {{ $def['label'] ?? $typeKey }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endif
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            <p class="text-xs text-base-content/40 mt-1">Chọn sai cặp type/subject_type sẽ báo lỗi rõ khi lưu.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">Nội dung</h2>

                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   placeholder="VD: Checklist E-E-A-T cho bài viết y khoa" maxlength="255" autofocus>
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nội dung (Markdown) <span class="text-error">*</span></span>
                            </label>
                            <textarea name="content" rows="12"
                                      class="textarea textarea-bordered textarea-sm w-full font-mono @error('content') textarea-error @enderror"
                                      placeholder="Nội dung tri thức...">{{ old('content') }}</textarea>
                            @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">Phạm vi áp dụng (scope)</h2>

                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phạm vi (JSON)</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc — để trống = áp dụng mọi bài/sản phẩm</span>
                            </label>
                            <textarea name="scope_json" rows="3"
                                      x-bind:disabled="!subjectType"
                                      class="textarea textarea-bordered textarea-sm w-full font-mono @error('scope_json') textarea-error @enderror">{{ old('scope_json') }}</textarea>
                            @error('scope_json')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            <template x-if="subjectType">
                                <p class="text-xs text-base-content/40 mt-1">
                                    Key phải khớp taxonomy_keys của <span class="font-mono" x-text="subjectType"></span>:
                                    <span class="font-mono" x-text="(taxonomy[subjectType] ?? []).join(', ')"></span>.
                                </p>
                            </template>
                            <template x-if="!subjectType">
                                <p class="text-xs text-warning mt-1">
                                    Chưa chọn subject_type ở trên (đang là "DNA chung toàn tổ chức") — tri thức DNA
                                    KHÔNG được đặt scope, để trống ô này (đã tự khoá).
                                </p>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Kiểu khớp phạm vi</span>
                                </label>
                                <select name="scope_match" class="select select-bordered select-sm w-full">
                                    <option value="any" {{ old('scope_match', 'any') === 'any' ? 'selected' : '' }}>any — khớp 1 key là đủ</option>
                                    <option value="all" {{ old('scope_match') === 'all' ? 'selected' : '' }}>all — phải khớp mọi key</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Độ ưu tiên</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Số nhỏ chèn trước</span>
                                </label>
                                <input type="number" name="priority" min="1" max="999"
                                       value="{{ old('priority') }}"
                                       class="input input-bordered input-sm w-full @error('priority') input-error @enderror"
                                       placeholder="Mặc định 100 (900 cho custom_note)">
                                @error('priority')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Lưu</p>
                    <div class="flex gap-2">
                        <a href="{{ route('backend.aicem.knowledge-documents.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo mới
                        </button>
                    </div>
                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection
