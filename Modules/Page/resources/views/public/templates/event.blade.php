@extends('layouts.frontend')

@section('title', $page->metaTitle())
@section('meta_description', $page->metaDescription())

@push('meta')
    @if($page->seo_noindex)
    <meta name="robots" content="noindex">
    @endif
    @if($page->getFirstMediaUrl('cover'))
    <meta property="og:image" content="{{ $page->getFirstMediaUrl('cover') }}">
    @endif
    <meta property="og:title" content="{{ $page->metaTitle() }}">
    @if($page->metaDescription())
    <meta property="og:description" content="{{ $page->metaDescription() }}">
    @endif
@endpush

{{--
    DEMO — spec/Page_Static_Pages_Technical_Specification.md §3.2.1 — file này minh hoạ cách 1
    dev thêm 1 template thiết kế riêng mới cho module Page:
      1. Tạo file view này dưới resources/views/public/templates/{key}.blade.php.
      2. Thêm 1 dòng vào Modules\Page\Features\PageManagement\PageTemplate::MAP với 'view' trỏ
         đúng tên view này (đã làm ở bước trước — key 'event').
      3. KHÔNG cần sửa Model/Controller/route — Admin chọn "Sự kiện" ở dropdown template, lưu,
         rồi "Xuất bản" là chạy được ngay (PublishPageAction tự kiểm tra View::exists() trước).

    Bố cục dưới đây tự do 100%, khác hẳn page::public.show (không có khối content mặc định bắt
    buộc) — vẫn dùng lại $page->title/excerpt/content/cover cho tiện demo, nhưng 1 template thật
    có thể bỏ qua các field này hoàn toàn nếu thiết kế không cần (§3.2.1). Không có field riêng
    cho "ngày tổ chức"/"địa điểm" vì v1 không có page_blocks (§11) — trang sự kiện thật cần các
    field đó thì đưa vào content, hoặc đó là dấu hiệu nên revisit §11 khi có nhiều nhu cầu tương tự.

    Xoá file này + dòng 'event' trong PageTemplate::MAP nếu không dùng đến.
--}}
@section('content')

<section class="relative bg-gradient-to-br from-primary to-primary/70 text-primary-content overflow-hidden">
    @if($page->getFirstMediaUrl('cover'))
    <div class="absolute inset-0">
        <img src="{{ $page->getFirstMediaUrl('cover') }}" alt="" class="w-full h-full object-cover opacity-20">
    </div>
    @endif
    <div class="relative max-w-4xl mx-auto px-4 py-24 text-center">
        <span class="badge badge-outline badge-lg mb-4">Sự kiện</span>
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $page->title }}</h1>
        @if($page->excerpt)
        <p class="text-lg opacity-90 max-w-2xl mx-auto">{{ $page->excerpt }}</p>
        @endif
        <a href="#noi-dung" class="btn btn-neutral btn-wide mt-8">Xem chi tiết</a>
    </div>
</section>

<div id="noi-dung" class="max-w-3xl mx-auto px-4 py-14">
    <div class="prose max-w-none">
        {!! $page->content !!}
    </div>
</div>

@endsection
