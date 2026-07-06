@extends('layouts.backend')
@section('title', 'Sửa: ' . $kcItem->title)


@section('content')
<div class="p-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Chỉnh sửa tài liệu</h1>
        <p class="text-sm opacity-60 mt-0.5">{{ Str::limit($kcItem->title, 60) }}</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.kc-items.update', $kcItem) }}" novalidate
          data-kc-item-form
          x-data="kcItemForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Nội dung chính ──────────────────────────────────────────── --}}
            <div class="space-y-5">

                {{-- Tiêu đề & Slug --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">

                        <div class="form-control">
                            <label class="label" for="title">
                                <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                            </label>
                            <input id="title" type="text" name="title"
                                   value="{{ old('title', $kcItem->title) }}"
                                   @input="onTitleInput($event.target.value)"
                                   data-req="Tiêu đề"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   placeholder="Tiêu đề tài liệu...">
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="slug">
                                <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs opacity-40">Tự động tạo từ tiêu đề</span>
                            </label>
                            <input id="slug" type="text" name="slug" x-model="slug"
                                   x-init="slug = '{{ old('slug', $kcItem->slug) }}'"
                                   class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror">
                            @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="summary">
                                <span class="label-text font-medium">Tóm tắt</span>
                                <span class="label-text-alt text-xs opacity-40">Hiển thị trong danh sách</span>
                            </label>
                            <textarea id="summary" name="summary"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('summary') textarea-error @enderror"
                                      data-jodit-preset="compact"
                                      rows="2">{{ old('summary', $kcItem->summary) }}</textarea>
                            @error('summary')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>

                {{-- Nội dung --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-3">
                        <p class="text-sm font-semibold">Nội dung</p>
                        <div class="form-control">
                            <textarea id="content" name="content"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('content') textarea-error @enderror"
                                      data-jodit-preset="standard"
                                      rows="16">{{ old('content', $kcItem->content) }}</textarea>
                            @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- File đính kèm --}}
                @can('update', $kcItem)
                <div class="card bg-base-100 shadow-sm border border-base-200"
                     x-data="kcAttachmentManager({
                         uploadUrl: '{{ route('backend.api.kc-items.attachments.store', $kcItem) }}',
                         maxMb: {{ config('kc.attachments.max_file_size_mb', 50) }},
                         maxTotalMb: {{ config('kc.attachments.max_item_total_mb', 200) }},
                     })">
                    <div class="card-body p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold">File đính kèm</p>
                            <span class="text-xs text-base-content/40" x-text="totalLabel + ' / {{ config('kc.attachments.max_item_total_mb', 200) }}MB'"></span>
                        </div>

                        {{-- Existing files --}}
                        <div id="kc-attach-existing"
                             data-files="{{ json_encode($kcItem->attachments->map(fn($a) => [
                                 'id' => $a->id,
                                 'file_name' => $a->file_name,
                                 'file_url' => $a->file_url,
                                 'file_type' => $a->file_type,
                                 'file_size_kb' => $a->file_size_kb,
                                 'delete_url' => route('backend.api.kc-items.attachments.destroy', [$kcItem, $a]),
                             ])) }}">
                        </div>

                        <div x-show="files.length > 0" class="space-y-1.5">
                            <template x-for="(f, i) in files" :key="f.id || i">
                                <div class="flex items-center gap-2 p-2 rounded-lg border border-base-200 text-xs">
                                    <svg class="w-4 h-4 shrink-0 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <a :href="f.file_url" target="_blank" class="flex-1 truncate hover:text-primary" x-text="f.file_name"></a>
                                    <span class="text-base-content/30 shrink-0" x-text="formatSize(f.file_size_kb)"></span>
                                    <button type="button" @click="deleteFile(f, i)"
                                            class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error shrink-0" title="Xóa file">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Upload zone --}}
                        <label class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-dashed border-base-300 hover:border-primary/50 hover:bg-base-200/50 transition-colors cursor-pointer text-center"
                               @dragover.prevent @drop.prevent="onFileDrop($event)">
                            <input type="file" multiple class="hidden" @change="onFileSelect($event)"
                                   accept="{{ implode(',', array_map(fn($e) => '.'.$e, config('kc.attachments.allowed_extensions', []))) }}">
                            <svg class="w-7 h-7 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <div>
                                <span class="text-sm font-medium text-primary">Chọn file</span>
                                <span class="text-sm text-base-content/50"> hoặc kéo thả vào đây</span>
                            </div>
                            <p class="text-xs text-base-content/30">PDF, DOCX, XLSX, PNG, JPG, MP4, ZIP — tối đa {{ config('kc.attachments.max_file_size_mb', 50) }}MB/file</p>
                        </label>

                        <div x-show="uploading" class="flex items-center gap-2 text-xs text-base-content/50">
                            <span class="loading loading-spinner loading-xs"></span> Đang upload...
                        </div>

                        <p x-show="error" x-text="error" class="text-xs text-error"></p>
                    </div>
                </div>
                @endcan

            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">

                {{-- Xuất bản --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-4">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Trạng thái</p>
                        <div>
                            @php
                                $statusVal = $kcItem->status instanceof \BackedEnum ? $kcItem->status->value : $kcItem->status;
                                $statusColor = match($statusVal) {
                                    'published' => 'badge-success',
                                    'draft'     => 'badge-warning',
                                    'archived'  => 'badge-neutral',
                                    default     => 'badge-ghost',
                                };
                                $statusLabel = match($statusVal) {
                                    'published' => 'Đã xuất bản',
                                    'draft'     => 'Bản nháp',
                                    'archived'  => 'Đã lưu trữ',
                                    default     => ucfirst($statusVal ?? '—'),
                                };
                            @endphp
                            <span class="badge {{ $statusColor }} badge-sm">{{ $statusLabel }}</span>
                        </div>
                        <div class="text-xs text-base-content/40 space-y-1">
                            <p>Tạo: {{ $kcItem->created_at->format('d/m/Y H:i') }}</p>
                            <p>Cập nhật: {{ $kcItem->updated_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Lưu thay đổi</button>
                            <a href="{{ route('backend.kc-items.show', $kcItem) }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>
                        <p class="text-center text-xs opacity-30">
                            <span class="text-error">*</span> là trường bắt buộc
                        </p>
                    </div>
                </div>

                {{-- Phân loại --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Phân loại</p>

                        {{-- Tổ chức --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                            </label>
                            @if($orgLocked)
                                <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                                <input type="text" value="{{ $organizations->first()->name }}" readonly
                                       class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                                <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                            @else
                                <select id="ts-organization" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn tổ chức —"
                                        data-req="Vui lòng chọn tổ chức">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ old('organization_id', $kcItem->organization_id) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="ts-type">
                                <span class="label-text text-sm font-medium">Loại tài liệu <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-type" name="type"
                                    class="select select-bordered select-sm w-full">
                                @foreach($types as $t)
                                <option value="{{ $t['value'] }}"
                                    {{ old('type', $kcItem->type instanceof \BackedEnum ? $kcItem->type->value : $kcItem->type) === $t['value'] ? 'selected' : '' }}>
                                    {{ $t['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="ts-category">
                                <span class="label-text text-sm font-medium">Danh mục <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-category" name="category_id"
                                    class="select select-bordered select-sm w-full"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.kc-category.options') }}"
                                        data-selected-value="{{ old('category_id', $kcItem->category_id) }}"
                                    @endif>
                                <option value="">— Chọn danh mục —</option>
                                @if($orgLocked)
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat['value'] }}"
                                        {{ old('category_id', $kcItem->category_id) == $cat['value'] ? 'selected' : '' }}>
                                        {{ $cat['text'] }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="ts-visibility">
                                <span class="label-text text-sm font-medium">Phạm vi hiển thị</span>
                            </label>
                            <select id="ts-visibility" name="visibility"
                                    class="select select-bordered select-sm w-full">
                                @foreach($visibilities as $v)
                                <option value="{{ $v['value'] }}"
                                    {{ old('visibility', $kcItem->visibility instanceof \BackedEnum ? $kcItem->visibility->value : $kcItem->visibility) === $v['value'] ? 'selected' : '' }}>
                                    {{ $v['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('visibility')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Domain TDWCF + Mức độ --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-sm font-medium">Domain TDWCF</span>
                                </label>
                                <select name="domain_code" class="select select-bordered select-sm w-full">
                                    <option value="">— Không chọn —</option>
                                    @foreach(['D1'=>'D1 — Số cơ bản','D2'=>'D2 — Dữ liệu','D3'=>'D3 — AI','D4'=>'D4 — Quy trình','D5'=>'D5 — Đổi mới','D6'=>'D6 — Hiệu suất'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('domain_code', $kcItem->domain_code) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('domain_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-sm font-medium">Mức độ</span>
                                </label>
                                <select name="difficulty" class="select select-bordered select-sm w-full">
                                    <option value="">— Không chọn —</option>
                                    <option value="1" {{ old('difficulty', $kcItem->difficulty) == 1 ? 'selected' : '' }}>1 — Cơ bản</option>
                                    <option value="2" {{ old('difficulty', $kcItem->difficulty) == 2 ? 'selected' : '' }}>2 — Trung cấp</option>
                                    <option value="3" {{ old('difficulty', $kcItem->difficulty) == 3 ? 'selected' : '' }}>3 — Nâng cao</option>
                                </select>
                                @error('difficulty')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Tags --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-2">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Tags</p>
                        <div class="form-control">
                            <select id="ts-tags" name="tags[]" multiple
                                    data-api-url="{{ $tagsApiUrl }}"
                                    class="select select-bordered select-sm w-full">
                                @foreach(old('tags', $selectedTags) as $tagId)
                                <option value="{{ $tagId }}" selected>{{ $tagId }}</option>
                                @endforeach
                            </select>
                            @error('tags')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            <p class="text-xs opacity-40 mt-1.5">Nhập tên tag và Enter để tạo mới</p>
                        </div>
                    </div>
                </div>

                {{-- Tùy chọn --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Tùy chọn</p>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_featured', $kcItem->is_featured) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium">Nổi bật</span>
                                <p class="text-xs opacity-50">Hiển thị trên trang chủ KC</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1"
                                   class="checkbox checkbox-sm checkbox-secondary mt-0.5 shrink-0"
                                   {{ old('is_pinned', $kcItem->is_pinned) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium">Ghim đầu</span>
                                <p class="text-xs opacity-50">Ghim đầu danh sách trong danh mục</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Hiệu lực --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Hiệu lực</p>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="fp-effective-date">
                                <span class="label-text text-xs">Ngày có hiệu lực</span>
                            </label>
                            <input id="fp-effective-date" name="effective_date"
                                   value="{{ old('effective_date', $kcItem->effective_date?->format('Y-m-d')) }}"
                                   class="input input-bordered input-sm w-full fp-init @error('effective_date') input-error @enderror"
                                   placeholder="dd/mm/yyyy" autocomplete="off">
                            @error('effective_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="fp-expired-date">
                                <span class="label-text text-xs">Ngày hết hiệu lực</span>
                            </label>
                            <input id="fp-expired-date" name="expired_date"
                                   value="{{ old('expired_date', $kcItem->expired_date?->format('Y-m-d')) }}"
                                   class="input input-bordered input-sm w-full fp-init @error('expired_date') input-error @enderror"
                                   placeholder="dd/mm/yyyy" autocomplete="off">
                            @error('expired_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

            </aside>

        </div>
    </form>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/jodit.js',
    'Modules/KcItem/resources/assets/sass/kc-item.scss',
    'Modules/KcItem/resources/assets/js/kc-item.js',
], 'build/backend')
@endpush
