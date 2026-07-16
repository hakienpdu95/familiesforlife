@extends('layouts.backend')
@section('title', 'Danh mục OCOP')

@section('content')
<div>

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
            <h1 class="text-2xl font-bold text-base-content">Danh mục OCOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Bảng phân loại sản phẩm OCOP chính thức theo quy định nhà nước — thống nhất toàn quốc, không chỉnh sửa.
            </p>
        </div>
        <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm">← Sản phẩm OCOP</a>
    </div>

    <div class="alert alert-info py-3 px-4 mb-5 flex items-start gap-3 text-sm">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Danh mục này đã được chuẩn hóa theo <span class="font-mono">spec/danhmuc.html</span> (bảng phân loại sản phẩm OCOP chính thức) — cố định theo quy định pháp luật, không còn thêm mới/sửa/xoá ở đây.</span>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="w-16 text-center">STT</th>
                        <th>Phân loại sản phẩm</th>
                        <th class="w-72">Cơ quan chủ trì quản lý</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categoryTree as $root)
                    @include('ocop::admin.categories._category-tree-row', ['item' => $root, 'depth' => 0])
                @empty
                <tr>
                    <td colspan="3" class="text-center py-8 text-base-content/40">Chưa có danh mục nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
