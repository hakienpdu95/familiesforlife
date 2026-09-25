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

<form method="POST" action="{{ route('backend.provinces.update', $province) }}" novalidate>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-4">

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
