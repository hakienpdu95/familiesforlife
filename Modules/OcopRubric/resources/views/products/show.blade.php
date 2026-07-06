@extends('layouts.backend')
@section('title', $product->name)

@section('content')
<div x-data="{ confirmDelete: false }">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $product->name }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                {{ $product->productGroup?->name }}
                @if ($product->product_code) · <span class="font-mono">{{ $product->product_code }}</span> @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ocop.products.index') }}" class="btn btn-ghost btn-sm">← Danh sách</a>
            @can(\App\Enums\PermissionEnum::OCOP_PRODUCT_MANAGE->value)
            <a href="{{ route('ocop.products.edit', $product) }}" class="btn btn-outline btn-sm">Sửa</a>
            @if ($product->status !== 'archived')
            <form method="POST" action="{{ route('ocop.products.archive', $product) }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">Lưu trữ</button>
            </form>
            @endif
            <button type="button" @click="confirmDelete = true" class="btn btn-error btn-outline btn-sm">Xóa</button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Kỷ lục luyện tập tốt nhất</h2>
                @if ($product->best_practice_score !== null)
                    <p class="text-2xl font-bold">{{ $product->best_practice_score }}đ</p>
                    <p class="text-sm text-base-content/60">Hạng {{ $product->best_practice_star_rank }} sao</p>
                @else
                    <p class="text-sm text-base-content/40">Chưa luyện tập lần nào.</p>
                @endif
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <h2 class="font-bold text-sm mb-2">Tự đánh giá mới nhất</h2>
                @if ($product->latest_self_assessment_score !== null)
                    <p class="text-2xl font-bold">{{ $product->latest_self_assessment_score }}đ</p>
                    <p class="text-sm text-base-content/60">Hạng {{ $product->latest_self_assessment_star_rank }} sao</p>
                    <p class="text-xs text-base-content/40 mt-1">Ước lượng tự chấm — không phải kết quả công nhận chính thức.</p>
                @else
                    <p class="text-sm text-base-content/40">Chưa tự đánh giá lần nào.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="flex gap-2 mb-5">
        @can(\App\Enums\PermissionEnum::OCOP_PRACTICE_USE->value)
        <form method="POST" action="{{ route('ocop.practice.create') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <button type="submit" class="btn btn-primary btn-sm">Luyện tập chấm điểm</button>
        </form>
        @endcan
        @can(\App\Enums\PermissionEnum::OCOP_SELF_ASSESS_USE->value)
        <form method="POST" action="{{ route('ocop.self_assessment.start', $product) }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Tự đánh giá (Mẫu 02)</button>
        </form>
        @endcan
    </div>

    <div x-cloak class="modal" :class="{ 'modal-open': confirmDelete }">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-base mb-2">Xóa sản phẩm?</h3>
            <p class="text-sm text-base-content/70 mb-4">Hành động này không thể hoàn tác.</p>
            <div class="modal-action gap-2">
                <button @click="confirmDelete = false" class="btn btn-ghost btn-sm">Hủy</button>
                <form method="POST" action="{{ route('ocop.products.destroy', $product) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm">Xóa</button>
                </form>
            </div>
        </div>
        <div @click="confirmDelete = false" class="modal-backdrop"></div>
    </div>
</div>
@endsection
