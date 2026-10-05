@extends('layouts.backend')
@section('title', 'Sửa tỉnh thành')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa tỉnh thành</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $province->name }}</p>
    </div>
    <a href="{{ route('backend.provinces.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.provinces.update', $province) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

        @php
            $mediaFields = ['logo', 'slogan', 'description', 'cover_image', 'tvc_video_url', 'vr360_map_url', 'highlight_tags'];
            $errorKeys = collect($errors->keys())->map(fn ($k) => \Illuminate\Support\Str::before($k, '.'));
            $initialTab = $errorKeys->intersect($mediaFields)->isNotEmpty() ? 'media' : 'admin';
            $tags = old('highlight_tags', $province->highlight_tags ?? []);
        @endphp
        <div class="card bg-base-100 shadow-sm border border-base-200" x-data="{ tab: '{{ $initialTab }}' }">

            <div class="border-b border-base-200 px-3">
                <nav class="flex -mb-px overflow-x-auto whitespace-nowrap" role="tablist">
                    @foreach(['admin' => 'Hành chính', 'media' => 'Truyền thông & Media'] as $key => $label)
                    <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'" @click="tab = '{{ $key }}'"
                            class="px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === '{{ $key }}' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        {{ $label }}
                    </button>
                    @endforeach
                </nav>
            </div>

            <div class="card-body p-4 space-y-4" x-show="tab === 'admin'">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên tỉnh/thành phố</span>
                        </label>
                        <input type="text" value="{{ $province->name }}" disabled
                               class="input input-bordered input-sm w-full">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên ngắn <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="short_name" value="{{ old('short_name', $province->short_name) }}" maxlength="255"
                               class="input input-bordered input-sm w-full @error('short_name') input-error @enderror" autofocus>
                        @error('short_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã tỉnh</span>
                        </label>
                        <input type="text" value="{{ $province->province_code }}" disabled
                               class="input input-bordered input-sm w-full font-mono">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Slug</span>
                        </label>
                        <input type="text" value="{{ $province->slug }}" disabled
                               class="input input-bordered input-sm w-full font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại</span>
                        </label>
                        <select disabled class="select select-bordered select-sm w-full">
                            @foreach($placeTypes as $t)
                            <option value="{{ $t['value'] }}" @selected($province->place_type === $t['value'])>{{ $t['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Vùng</span>
                        </label>
                        <select disabled class="select select-bordered select-sm w-full">
                            @foreach($regions as $r)
                            <option value="{{ $r->id }}" @selected($province->region_id === $r->id)>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>

            <div class="card-body p-4 space-y-5" x-show="tab === 'media'" x-cloak>

                <div class="form-control" x-data="{ preview: {{ Js::from($province->logoUrl()) }}, remove: {{ old('remove_logo') ? 'true' : 'false' }} }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Logo</span>
                        <span class="label-text-alt text-xs text-base-content/40">PNG nền trong suốt, JPG hoặc WEBP — tối đa 2MB, nên là ảnh vuông</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-base-300 bg-base-200">
                            <template x-if="preview && !remove">
                                <img :src="preview" alt="Logo {{ $province->name }}" class="h-full w-full object-contain p-1">
                            </template>
                            <template x-if="!preview || remove">
                                <span class="text-2xl font-bold text-base-content/30">{{ mb_strtoupper(mb_substr($province->short_name ?: $province->name, 0, 1)) }}</span>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                                   @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); remove = false; }"
                                   class="file-input file-input-bordered file-input-sm w-full @error('logo') file-input-error @enderror">
                            @error('logo')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @if($province->logo)
                            <label class="mt-2 flex cursor-pointer items-center gap-2 text-xs text-error">
                                <input type="checkbox" name="remove_logo" value="1" x-model="remove" class="checkbox checkbox-xs checkbox-error">
                                Xoá logo hiện tại
                            </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Khẩu hiệu (slogan)</span>
                        <span class="label-text-alt text-xs text-base-content/40">Thay cho dòng mô tả ở hero trang địa phương</span>
                    </label>
                    <input type="text" name="slogan" value="{{ old('slogan', $province->slogan) }}" maxlength="255"
                           placeholder="VD: Huế - Xứ sở hạnh phúc"
                           class="input input-bordered input-sm w-full @error('slogan') input-error @enderror">
                    @error('slogan')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Giới thiệu điểm đến</span>
                    </label>
                    <textarea name="description" rows="5" maxlength="5000"
                              placeholder="Đoạn giới thiệu tổng quan về tỉnh/thành..."
                              class="textarea textarea-bordered textarea-sm w-full leading-relaxed @error('description') textarea-error @enderror">{{ old('description', $province->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control"
                     x-data="{ tags: {{ Js::from(array_values($tags)) }}, draft: '',
                               add() { const t = this.draft.trim().replace(/,$/, '').trim(); if (t && !this.tags.includes(t) && this.tags.length < 10) this.tags.push(t); this.draft = ''; } }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Thẻ nổi bật</span>
                        <span class="label-text-alt text-xs text-base-content/40" x-text="tags.length + ' / 10 thẻ'"></span>
                    </label>
                    <div class="flex min-h-9 flex-wrap items-center gap-1.5 rounded-lg border border-base-300 px-2 py-1.5 focus-within:border-primary @error('highlight_tags') border-error @enderror @error('highlight_tags.*') border-error @enderror">
                        <template x-for="(tag, i) in tags" :key="tag">
                            <span class="badge badge-primary badge-outline gap-1 py-2.5">
                                <span x-text="tag"></span>
                                <input type="hidden" name="highlight_tags[]" :value="tag">
                                <button type="button" class="opacity-60 hover:opacity-100" @click="tags.splice(i, 1)" :aria-label="'Xoá thẻ ' + tag">✕</button>
                            </span>
                        </template>
                        <input type="text" x-model="draft" maxlength="50"
                               @keydown.enter.prevent="add()" @keydown.comma.prevent="add()" @blur="add()"
                               @keydown.backspace="if (!draft) tags.pop()"
                               placeholder="VD: Kinh đô Ẩm thực — Enter để thêm"
                               class="min-w-40 flex-1 bg-transparent text-sm outline-none">
                    </div>
                    <p class="mt-1 text-xs text-base-content/40">Danh xưng đặc trưng hiển thị ở hero, VD: Kinh đô Ẩm thực, Kinh đô Áo dài, Festival Huế.</p>
                    @error('highlight_tags')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    @error('highlight_tags.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control" x-data="{ preview: {{ Js::from($province->coverImageUrl()) }}, remove: {{ old('remove_cover_image') ? 'true' : 'false' }} }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ảnh cover / banner</span>
                        <span class="label-text-alt text-xs text-base-content/40">JPG, PNG, WEBP — tối đa 4MB, khuyến nghị 1920×600</span>
                    </label>
                    <template x-if="preview && !remove">
                        <div class="relative mb-2 aspect-[16/5] overflow-hidden rounded-lg border border-base-300 bg-base-200">
                            <img :src="preview" alt="Ảnh cover {{ $province->name }}" class="h-full w-full object-cover">
                        </div>
                    </template>
                    <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"
                           @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); remove = false; }"
                           class="file-input file-input-bordered file-input-sm w-full @error('cover_image') file-input-error @enderror">
                    @error('cover_image')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    @if($province->cover_image)
                    <label class="mt-2 flex cursor-pointer items-center gap-2 text-xs text-error">
                        <input type="checkbox" name="remove_cover_image" value="1" x-model="remove" class="checkbox checkbox-xs checkbox-error">
                        Xoá ảnh cover hiện tại
                    </label>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Video TVC quảng bá</span>
                        </label>
                        <input type="url" name="tvc_video_url" value="{{ old('tvc_video_url', $province->tvc_video_url) }}" maxlength="500"
                               placeholder="https://www.youtube.com/watch?v=..."
                               class="input input-bordered input-sm w-full @error('tvc_video_url') input-error @enderror">
                        <p class="mt-1 text-xs text-base-content/40">Link YouTube/Vimeo.</p>
                        @error('tvc_video_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Bản đồ VR/AR 360</span>
                        </label>
                        <input type="url" name="vr360_map_url" value="{{ old('vr360_map_url', $province->vr360_map_url) }}" maxlength="500"
                               placeholder="https://..."
                               class="input input-bordered input-sm w-full @error('vr360_map_url') input-error @enderror">
                        <p class="mt-1 text-xs text-base-content/40">Link trải nghiệm thực tế ảo của điểm đến.</p>
                        @error('vr360_map_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

            </div>

        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-3">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Hiển thị</p>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-1">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1"
                               class="checkbox checkbox-sm checkbox-warning mt-0.5 shrink-0" @checked(old('is_featured', $province->is_featured))>
                        <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị nổi bật</span>
                    </label>
                    <p class="text-xs text-base-content/50 mb-1 pl-7">Đang chọn {{ $featuredCount }}/{{ $featuredMax }} tỉnh/thành</p>
                    @error('is_featured')<p class="text-xs text-error mb-1 pl-7">{{ $message }}</p>@enderror
                    <div class="mb-4"></div>


                    <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" @checked(old('is_active', $province->is_active))>
                        <span class="text-sm font-medium group-hover:text-primary transition-colors">Hoạt động</span>
                    </label>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Thứ tự hiển thị</span>
                            <span class="label-text-alt text-xs text-base-content/40">Thứ tự tab</span>
                        </label>
                        <input type="number" name="order_column" min="0" value="{{ old('order_column', $province->order_column) }}"
                               class="input input-bordered input-sm w-full @error('order_column') input-error @enderror">
                        @error('order_column')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.provinces.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu thay đổi
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
