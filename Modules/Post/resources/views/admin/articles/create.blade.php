@extends('layouts.backend')
@section('title', 'Thêm bài viết')

@section('content')
<div class="max-w-2xl">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm bài viết</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo bài viết và tiếp tục soạn nội dung chi tiết/SEO ngay sau khi lưu</p>
    </div>
    <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.post.articles.store') }}" novalidate data-article-form
      class="card bg-base-100 shadow-sm border border-base-200">
    @csrf
    <div class="card-body p-5 space-y-4">

        {{-- Gộp bước tạo bản dịch đầu tiên ngay tại đây — bấm "Tạo bài viết" sẽ tạo cả vỏ
             PostArticle lẫn bản dịch ở ngôn ngữ chính (title này) trong 1 lượt submit, sau đó
             vào thẳng trang sửa bài với editor đầy đủ (nội dung/SEO/khối sản phẩm), khôi phục
             đúng trải nghiệm "1 bước tạo xong" trước khi PostArticle tách vỏ đa ngôn ngữ. --}}
        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
            </label>
            <input type="text" name="title" value="{{ old('title') }}"
                   data-req="Vui lòng nhập tiêu đề" data-val-maxlength="300"
                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                   placeholder="VD: 5 mẹo giúp bé ngủ ngon" maxlength="300" autofocus>
            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1">
                <span class="label-text text-xs font-medium">Ngôn ngữ chính</span>
            </label>
            <select name="main_locale" class="select select-bordered select-sm w-full @error('main_locale') select-error @enderror">
                @foreach(config('post.locales') as $code => $label)
                <option value="{{ $code }}" {{ old('main_locale', config('post.default_locale')) === $code ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('main_locale')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1">
                <span class="label-text text-xs font-medium">Định dạng nội dung</span>
            </label>
            <select name="format" class="select select-bordered select-sm w-full @error('format') select-error @enderror">
                @foreach(\Modules\Post\Enums\ArticleFormat::cases() as $f)
                <option value="{{ $f->value }}" {{ old('format', 'article') === $f->value ? 'selected' : '' }}>{{ $f->label() }}</option>
                @endforeach
            </select>
            @error('format')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1">
                <span class="label-text text-xs font-medium">Ảnh đại diện (URL)</span>
            </label>
            <input type="text" name="cover_image_url" value="{{ old('cover_image_url') }}"
                   class="input input-bordered input-sm w-full @error('cover_image_url') input-error @enderror"
                   placeholder="https://...">
            @error('cover_image_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        {{-- spec/Province_Showcase_Technical_Specification.md §6.3 — tuỳ chọn, không bắt buộc. --}}
        <x-address-picker
            :required="false"
            instance-id="article-create"
            name-province="province_code"
            name-ward="ward_code"
            :province-value="old('province_code')"
            :ward-value="old('ward_code')"
        />

        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Danh mục</span>
                <span class="label-text-alt text-xs text-base-content/40">Bấm ★ để đặt danh mục chính</span>
            </label>
            @include('post::admin.articles._category-picker', [
                'categoryTree' => $categoryTree,
                'selectedCategoryIds' => old('category_ids', []),
                'primaryCategoryId' => old('is_primary_category_id'),
            ])
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Tags</span>
            </label>
            <input type="text" name="tags" value="{{ old('tags') }}"
                   class="input input-bordered input-sm w-full" placeholder="ngủ, sơ sinh, mẹo hay">
        </div>

        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
            <input type="hidden" name="is_featured" value="0">
            <input type="checkbox" name="is_featured" value="1"
                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured') ? 'checked' : '' }}>
            <div>
                <span class="text-sm font-medium group-hover:text-primary transition-colors">Bài viết nổi bật</span>
                <p class="text-xs text-base-content/50 mt-0.5">Ưu tiên hiển thị ở trang chủ</p>
            </div>
        </label>

        <div class="flex gap-2 pt-2">
            <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm flex-1">Tạo bài viết</button>
        </div>
    </div>
</form>
</div>
@endsection

@push('styles')
    @vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/jodit.js',
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
@endpush
