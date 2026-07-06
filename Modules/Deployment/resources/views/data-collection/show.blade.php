@extends('layouts.backend')
@section('title', 'Thu thập dữ liệu — ' . $orgName)

@section('content')
<div class="max-w-2xl mx-auto pb-20" x-data="dataCollectionForm()">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-base-content/50 mb-1">
            <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
               class="hover:underline">← Quay lại</a>
        </div>
        <h1 class="text-xl font-bold">📋 Thu thập dữ liệu thực địa</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Tổ chức: <strong>{{ $orgName }}</strong>
            @if($isComplete)
                <span class="badge badge-success badge-sm ml-2">Hoàn thành</span>
            @else
                <span class="badge badge-warning badge-sm ml-2">Đang thu thập</span>
            @endif
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Sections --}}
    @foreach($sections as $section)
    @php
        $sectionFieldIds = $section->fields->pluck('id');
        $filledCount     = $existingAnswers->whereIn('field_id', $sectionFieldIds)->count();
        $totalFields     = $section->fields->count();
        $requiredDone    = $section->fields->where('is_required', true)->filter(fn($f) => isset($existingAnswers[$f->id]))->count();
        $requiredTotal   = $section->fields->where('is_required', true)->count();
    @endphp

    <div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
        <div class="card-body">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div>
                    <h2 class="font-semibold text-base">
                        {{ $section->icon ?? '' }} {{ $section->title }}
                    </h2>
                    <p class="text-xs text-base-content/50 mt-0.5">
                        {{ $filledCount }}/{{ $totalFields }} trường đã điền
                        @if($requiredTotal > 0)
                            · Bắt buộc: {{ $requiredDone }}/{{ $requiredTotal }}
                        @endif
                    </p>
                </div>
                @if($requiredDone === $requiredTotal && $requiredTotal > 0)
                    <span class="badge badge-success badge-sm shrink-0 mt-1">✓ Đủ</span>
                @endif
            </div>

            <form method="POST"
                  action="{{ route('deployment.data-collection.submit-section', ['vertical' => $vertical->code(), 'target' => $target->id, 'sectionCode' => $section->section_code]) }}">
                @csrf
                <div class="space-y-4">

                    @foreach($section->fields as $field)
                    @php
                        $existing = $existingAnswers[$field->id] ?? null;
                        $currentVal = $existing?->value_string ?? $existing?->value_text ?? $existing?->value_number ?? $existing?->value_bool ?? $existing?->value_date ?? '';
                        $isGpsLat = $field->field_key === 'gps_lat';
                        $isGpsLng = $field->field_key === 'gps_lng';
                    @endphp

                    <div @if($isGpsLat || $isGpsLng) x-data="gpsField('{{ $field->field_key }}')" @endif>
                        <label class="block text-sm font-medium mb-1">
                            {{ $field->label }}
                            @if($field->is_required) <span class="text-error">*</span> @endif
                        </label>

                        @if($field->field_type == 2) {{-- Textarea --}}
                            <textarea name="answers[{{ $field->id }}]"
                                      class="textarea textarea-bordered w-full text-sm"
                                      rows="4"
                                      placeholder="{{ $field->placeholder ?? '' }}"
                                      @if($field->is_required) required @endif>{{ $currentVal }}</textarea>

                        @elseif($field->field_type == 3) {{-- Number --}}
                            <input type="number"
                                   name="answers[{{ $field->id }}]"
                                   value="{{ $currentVal }}"
                                   class="input input-bordered w-full text-sm"
                                   placeholder="{{ $field->placeholder ?? '' }}"
                                   step="any"
                                   @if($field->is_required) required @endif>

                        @elseif($field->field_type == 9) {{-- Boolean --}}
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="answers[{{ $field->id }}]" value="1"
                                           class="radio radio-sm radio-primary"
                                           {{ $currentVal == '1' || $currentVal === true ? 'checked' : '' }}>
                                    <span class="text-sm">Có</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="answers[{{ $field->id }}]" value="0"
                                           class="radio radio-sm"
                                           {{ $currentVal == '0' || ($currentVal !== '' && !$currentVal) ? 'checked' : '' }}>
                                    <span class="text-sm">Không</span>
                                </label>
                            </div>

                        @elseif($field->field_type == 8) {{-- Date --}}
                            <input type="date"
                                   name="answers[{{ $field->id }}]"
                                   value="{{ $currentVal }}"
                                   class="input input-bordered w-full text-sm"
                                   @if($field->is_required) required @endif>

                        @else {{-- Text (default) --}}
                            @if($isGpsLat || $isGpsLng)
                            <div class="flex gap-2">
                                <input type="text"
                                       name="answers[{{ $field->id }}]"
                                       x-ref="input"
                                       :value="value || '{{ $currentVal }}'"
                                       class="input input-bordered flex-1 text-sm font-mono"
                                       placeholder="{{ $field->placeholder ?? '' }}"
                                       @if($field->is_required) required @endif>
                                <button type="button"
                                        @click="capture"
                                        class="btn btn-sm btn-outline gap-1 shrink-0"
                                        :class="loading ? 'loading' : ''"
                                        :disabled="loading">
                                    <span x-show="!loading">📍 GPS</span>
                                    <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                                </button>
                            </div>
                            <p x-show="error" x-text="error" class="text-xs text-error mt-1"></p>
                            @else
                            <input type="text"
                                   name="answers[{{ $field->id }}]"
                                   value="{{ $currentVal }}"
                                   class="input input-bordered w-full text-sm"
                                   placeholder="{{ $field->placeholder ?? '' }}"
                                   @if($field->is_required) required @endif>
                            @endif
                        @endif

                        @if($field->placeholder && !in_array($field->field_type, [2]))
                            <p class="text-xs text-base-content/40 mt-1">{{ $field->placeholder }}</p>
                        @endif
                    </div>
                    @endforeach

                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="btn btn-primary btn-sm gap-2">
                        💾 Lưu {{ $section->title }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Media Upload Panel --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
        <div class="card-body">
            <h2 class="font-semibold text-base mb-3">📎 Hồ sơ pháp lý & Tài liệu</h2>

            <form method="POST"
                  action="{{ route('deployment.media.store', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
                  enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Loại tài liệu</label>
                        <select name="collection" class="select select-bordered select-sm w-full">
                            <option value="legal_docs">Hồ sơ pháp lý (ĐKKD, CCCD, chứng nhận...)</option>
                            <option value="field_photos">Ảnh thực địa vùng sản xuất</option>
                            <option value="history_files">File lịch sử hoạt động (Excel)</option>
                            <option value="donvi_files">File đơn vị chi tiết (Excel)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">File</label>
                        <input type="file" name="file" class="file-input file-input-bordered file-input-sm w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Mô tả (tuỳ chọn)</label>
                        <input type="text" name="description" class="input input-bordered input-sm w-full"
                               placeholder="Ví dụ: ĐKKD bản scan 2024">
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="btn btn-secondary btn-sm gap-2">
                        📤 Upload
                    </button>
                </div>
            </form>

            {{-- Media list via API --}}
            <div x-data="mediaList('{{ route('deployment.media.index', ['vertical' => $vertical->code(), 'target' => $target->id]) }}')"
                 x-init="load()"
                 class="mt-4">
                <template x-if="items.length > 0">
                    <div class="space-y-2">
                        <p class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Đã upload</p>
                        <template x-for="item in items" :key="item.id">
                            <div class="flex items-center gap-2 text-sm py-1.5 border-b border-base-200">
                                <span class="flex-1 truncate" x-text="item.name"></span>
                                <span class="text-xs text-base-content/40" x-text="item.size"></span>
                                <a :href="item.url" target="_blank" class="btn btn-ghost btn-xs">👁</a>
                                <form method="POST"
                                      :action="'{{ route('deployment.media.destroy', ['vertical' => $vertical->code(), 'target' => $target->id, 'mediaId' => '__ID__']) }}'.replace('__ID__', item.id)"
                                      @submit.prevent="remove(item.id, $el)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs text-error">✕</button>
                                </form>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="items.length === 0 && !loading">
                    <p class="text-xs text-base-content/40 mt-2">Chưa có file nào.</p>
                </template>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function dataCollectionForm() {
    return {};
}

function gpsField(fieldKey) {
    return {
        value: '',
        loading: false,
        error: '',
        capture() {
            if (!navigator.geolocation) {
                this.error = 'Trình duyệt không hỗ trợ GPS.';
                return;
            }
            this.loading = true;
            this.error = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const v = fieldKey === 'gps_lat'
                        ? pos.coords.latitude.toFixed(6)
                        : pos.coords.longitude.toFixed(6);
                    this.value = v;
                    this.$refs.input.value = v;
                    this.loading = false;
                },
                (err) => {
                    this.error = 'Không lấy được GPS: ' + err.message;
                    this.loading = false;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    };
}

function mediaList(indexUrl) {
    return {
        items: [],
        loading: true,
        async load(collection = 'legal_docs') {
            this.loading = true;
            try {
                const res = await fetch(indexUrl + '?collection=' + collection, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.items = data.media || [];
            } catch (e) {}
            this.loading = false;
        },
        async remove(id, form) {
            if (!confirm('Xóa file này?')) return;
            const action = form.action;
            const res = await fetch(action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new URLSearchParams({ _method: 'DELETE' }),
            });
            if (res.ok) {
                this.items = this.items.filter(i => i.id !== id);
            }
        }
    };
}
</script>
@endpush

@endsection
