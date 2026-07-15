@extends('layouts.backend')
@section('title', 'Sửa danh mục OCOP')

@section('content')
<div class="max-w-lg">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa danh mục OCOP</h1>
    </div>
    <a href="{{ route('backend.ocop.categories.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.ocop.categories.update', $category) }}" novalidate
      class="card bg-base-100 shadow-sm border border-base-200">
    @csrf
    @method('PUT')
    <div class="card-body p-5 space-y-4">

        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Tên danh mục <span class="text-error">*</span></span>
            </label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}"
                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                   maxlength="150" autofocus>
            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Icon</span>
            </label>
            <input type="text" name="icon" value="{{ old('icon', $category->icon) }}"
                   class="input input-bordered input-sm w-full @error('icon') input-error @enderror"
                   maxlength="80">
            @error('icon')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Thứ tự hiển thị</span>
            </label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $category->sort_order) }}"
                   class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
            @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
            <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị danh mục</span>
        </label>

        <div class="flex gap-2 pt-2">
            <a href="{{ route('backend.ocop.categories.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm flex-1">Lưu thay đổi</button>
        </div>
    </div>
</form>
</div>
@endsection
