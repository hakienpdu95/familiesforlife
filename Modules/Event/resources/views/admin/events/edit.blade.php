@extends('layouts.backend')
@section('title', 'Sửa sự kiện')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa sự kiện</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex items-center gap-2">
            {{ $event->title }}
            <span class="badge badge-sm {{ $event->status->badgeClass() }}">{{ $event->status->label() }}</span>
        </p>
    </div>
    <a href="{{ route('backend.event.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.event.update', $event) }}" enctype="multipart/form-data"
      novalidate data-event-form
      x-data="{ locationType: '{{ old('location_type', $event->location_type->value) }}', priceType: '{{ old('price_type', $event->price_type->value) }}' }">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start">

        <div class="space-y-5">

            {{-- ── Thông tin cơ bản ─────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Thông tin cơ bản</h2>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Danh mục <span class="text-error">*</span></span></label>
                        <select id="ts-category" name="category_id"
                                class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                data-req="Vui lòng chọn danh mục">
                            <option value=""></option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id', $event->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span></label>
                        <input type="text" name="title" value="{{ old('title', $event->title) }}"
                               data-req="Vui lòng nhập tiêu đề" data-val-maxlength="150"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               maxlength="150" autofocus>
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control" x-data="{ len: {{ strlen(old('short_title', $event->short_title)) }} }">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề rút gọn <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs" :class="len > 55 ? 'text-error' : 'text-base-content/40'" x-text="len + ' / 55 ký tự'"></span>
                        </label>
                        <input type="text" name="short_title" value="{{ old('short_title', $event->short_title) }}"
                               x-on:input="len = $event.target.value.length"
                               data-req="Vui lòng nhập tiêu đề rút gọn" data-val-maxlength="55"
                               class="input input-bordered input-sm w-full @error('short_title') input-error @enderror"
                               maxlength="55">
                        @error('short_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả <span class="text-error">*</span></span></label>
                        <textarea name="description" rows="5"
                                  data-req="Vui lòng nhập mô tả sự kiện"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $event->description) }}</textarea>
                        <p class="text-xs text-base-content/40 mt-1">Không chèn liên kết (http://, https://, www.) — sẽ bị từ chối khi lưu.</p>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Thời gian ─────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Thời gian</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày bắt đầu <span class="text-error">*</span></span></label>
                            <input type="text" name="start_date" id="fp-start-date"
                                   value="{{ old('start_date', $event->start_date?->format('Y-m-d')) }}"
                                   data-req="Vui lòng chọn ngày bắt đầu"
                                   class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror">
                            @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày kết thúc <span class="text-error">*</span></span></label>
                            <input type="text" name="end_date" id="fp-end-date"
                                   value="{{ old('end_date', $event->end_date?->format('Y-m-d')) }}"
                                   data-req="Vui lòng chọn ngày kết thúc"
                                   class="input input-bordered input-sm w-full fp-init @error('end_date') input-error @enderror">
                            @error('end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giờ bắt đầu</span>
                                <span class="label-text-alt text-xs text-base-content/40">Bỏ trống = cả ngày</span>
                            </label>
                            <input type="time" name="start_time" value="{{ old('start_time', $event->start_time) }}"
                                   class="input input-bordered input-sm w-full @error('start_time') input-error @enderror">
                            @error('start_time')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Giờ kết thúc</span></label>
                            <input type="time" name="end_time" value="{{ old('end_time', $event->end_time) }}"
                                   class="input input-bordered input-sm w-full @error('end_time') input-error @enderror">
                            @error('end_time')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Địa điểm ──────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Địa điểm</h2>

                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="location_type" value="physical" x-model="locationType" class="radio radio-sm radio-primary" {{ old('location_type', $event->location_type->value) === 'physical' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Trực tiếp</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="location_type" value="online" x-model="locationType" class="radio radio-sm radio-primary" {{ old('location_type', $event->location_type->value) === 'online' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Trực tuyến</span>
                        </label>
                    </div>

                    <div x-show="locationType === 'physical'" x-cloak class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên địa điểm <span class="text-error">*</span></span></label>
                                <input type="text" name="venue_name" value="{{ old('venue_name', $event->venue_name) }}"
                                       class="input input-bordered input-sm w-full @error('venue_name') input-error @enderror">
                                @error('venue_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Địa chỉ <span class="text-error">*</span></span></label>
                                <input type="text" name="venue_address" value="{{ old('venue_address', $event->venue_address) }}"
                                       class="input input-bordered input-sm w-full @error('venue_address') input-error @enderror">
                                @error('venue_address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <x-address-picker
                            :required="false"
                            instance-id="event-venue"
                            name-province="province_code"
                            name-ward="ward_code"
                            :province-value="old('province_code', $event->province_code)"
                            :ward-value="old('ward_code', $event->ward_code)"
                        />
                    </div>

                    <div x-show="locationType === 'online'" x-cloak class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Link tham gia <span class="text-error">*</span></span></label>
                        <input type="url" name="online_url" value="{{ old('online_url', $event->online_url) }}"
                               class="input input-bordered input-sm w-full @error('online_url') input-error @enderror">
                        @error('online_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- spec/Heritage_Technical_Specification.md §8.1 — tuỳ chọn, không bắt buộc. --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Di tích liên quan</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <select id="ts-heritage-site" name="heritage_site_id"
                                class="select select-bordered select-sm w-full ts-init @error('heritage_site_id') select-error @enderror"
                                data-ts-placeholder="— Không gắn di tích —">
                            <option value=""></option>
                            @foreach($heritageSites as $s)
                            <option value="{{ $s->id }}" {{ (string) old('heritage_site_id', $event->heritage_site_id) === (string) $s->id ? 'selected' : '' }}>
                                {{ $s->name }}{{ $s->province_name ? " ({$s->province_name})" : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('heritage_site_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Website / Vé <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Link mua vé/đăng ký, hoặc website chính thức</span>
                        </label>
                        <input type="url" name="website_url" value="{{ old('website_url', $event->website_url) }}"
                               data-req="Vui lòng nhập link website/vé"
                               class="input input-bordered input-sm w-full @error('website_url') input-error @enderror">
                        @error('website_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Giá vé ────────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Giá vé</h2>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="price_type" value="free" x-model="priceType" class="radio radio-sm radio-primary" {{ old('price_type', $event->price_type->value) === 'free' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Miễn phí</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="price_type" value="single" x-model="priceType" class="radio radio-sm radio-primary" {{ old('price_type', $event->price_type->value) === 'single' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Giá cố định</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="price_type" value="range" x-model="priceType" class="radio radio-sm radio-primary" {{ old('price_type', $event->price_type->value) === 'range' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Khoảng giá</span>
                        </label>
                    </div>

                    <div x-show="priceType === 'single'" x-cloak class="form-control sm:w-64">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Giá vé (VNĐ) <span class="text-error">*</span></span></label>
                        <input type="number" name="price_amount" min="0" step="1000" value="{{ old('price_amount', $event->price_amount) }}"
                               class="input input-bordered input-sm w-full @error('price_amount') input-error @enderror">
                        @error('price_amount')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="priceType === 'range'" x-cloak class="grid grid-cols-2 gap-4 sm:w-96">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Từ (VNĐ) <span class="text-error">*</span></span></label>
                            <input type="number" name="price_min" min="0" step="1000" value="{{ old('price_min', $event->price_min) }}"
                                   class="input input-bordered input-sm w-full @error('price_min') input-error @enderror">
                            @error('price_min')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đến (VNĐ) <span class="text-error">*</span></span></label>
                            <input type="number" name="price_max" min="0" step="1000" value="{{ old('price_max', $event->price_max) }}"
                                   class="input input-bordered input-sm w-full @error('price_max') input-error @enderror">
                            @error('price_max')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Poster ────────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Poster</h2>

                    @if($event->poster_path)
                    <div class="flex items-center gap-3">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($event->poster_path) }}" alt="{{ $event->poster_alt ?? $event->title }}" class="h-20 rounded-lg border border-base-200 object-cover">
                        <p class="text-xs text-base-content/50">Poster hiện tại — chọn ảnh mới bên dưới nếu muốn thay.</p>
                    </div>
                    @endif

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">{{ $event->poster_path ? 'Thay ảnh poster' : 'Ảnh poster' }} @if(!$event->poster_path)<span class="text-error">*</span>@endif</span></label>
                        <input type="file" name="poster" accept="image/jpeg,image/png"
                               @if(!$event->poster_path) data-req="Vui lòng chọn ảnh poster" @endif
                               class="file-input file-input-bordered file-input-sm w-full @error('poster') file-input-error @enderror">
                        <p class="text-xs text-base-content/40 mt-1">JPG hoặc PNG, tối đa 1MB, khuyến nghị 1400×1000 (ngang).</p>
                        @error('poster')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả ảnh (alt text)</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="poster_alt" value="{{ old('poster_alt', $event->poster_alt) }}"
                               class="input input-bordered input-sm w-full @error('poster_alt') input-error @enderror"
                               placeholder="Mặc định dùng tiêu đề sự kiện">
                        @error('poster_alt')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 space-y-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Xuất bản</p>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured', $event->is_featured) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Ghim nổi bật</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Ưu tiên hiển thị hero trang chủ sau khi xuất bản</p>
                        </div>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('backend.event.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu thay đổi
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('styles')
    @vite(['Modules/Event/resources/assets/sass/event.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Event/resources/assets/js/event.js',
    ], 'build/backend')
@endpush
