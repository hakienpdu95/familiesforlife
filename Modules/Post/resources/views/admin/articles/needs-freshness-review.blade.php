@extends('layouts.backend')
@section('title', 'Bài viết cần rà soát')

@section('content')
<div x-data="articleFreshnessPage({{ Js::from(['rows' => $rows]) }})">
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Bài viết cần rà soát</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Bài viết đã xuất bản, xem nhiều nhưng lâu chưa cập nhật (quá 90 ngày) — ưu tiên rà soát nội dung/số
            liệu trước, theo đúng gợi ý "rà soát định kỳ mỗi quý" ở tab GEO Checklist của trình soạn bài.
            Danh sách chỉ liệt kê theo lượt xem + ngày cập nhật sẵn có, không tự đánh giá nội dung còn đúng hay không.
        </p>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="freshness-table"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
@endpush
