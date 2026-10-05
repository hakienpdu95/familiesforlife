@extends('layouts.frontend')

@section('title', 'Đã Gửi Sự Kiện')

@section('content')
@php
    $steps = [
        ['Biên tập viên xem xét', 'Đội ngũ biên tập kiểm tra nội dung, thời gian, địa điểm và poster của sự kiện.'],
        ['Nhận email thông báo', 'Bạn sẽ nhận email báo kết quả duyệt — địa chỉ email của bạn không bao giờ hiển thị công khai.'],
        ['Sự kiện được đăng', 'Sau khi được duyệt, sự kiện xuất hiện trong mục Sự kiện và có thể được giới thiệu trên trang chủ.'],
    ];
@endphp
<div class="container">
<div class="mx-auto max-w-4xl py-10 lg:py-16">

    <header class="text-center">
        <div class="mx-auto flex h-28 w-28 rotate-[-10deg] items-center justify-center rounded-full bg-primary text-center font-serif text-lg font-bold leading-tight text-white">
            Đã gửi<br>thành công!
        </div>
        <span class="mt-8 block text-xs font-bold uppercase tracking-widest text-primary">Gửi sự kiện</span>
        <h1 class="mt-3 font-serif text-3xl font-bold text-gray-900 lg:text-4xl">Cảm Ơn Bạn Đã Chia Sẻ!</h1>
        <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-gray-600">
            Sự kiện của bạn đã được gửi thành công và đang chờ đội ngũ biên tập xem xét.
            Chúng tôi sẽ gửi email thông báo kết quả trong thời gian sớm nhất.
        </p>
    </header>

    <section class="mt-14">
        <h2 class="mb-6 border-b border-gray-200 pb-3 text-lg font-bold uppercase tracking-widest text-gray-900">Bước tiếp theo</h2>
        <ol class="grid grid-cols-1 gap-8 md:grid-cols-3">
            @foreach($steps as [$title, $text])
            <li>
                <span class="font-serif text-4xl font-bold text-primary">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <h3 class="mt-2 text-sm font-bold uppercase tracking-wider text-gray-900">{{ $title }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $text }}</p>
            </li>
            @endforeach
        </ol>
    </section>

    <div class="mt-14 flex flex-col items-center justify-center gap-4 border-t border-gray-200 pt-10 sm:flex-row">
        <a href="{{ route('post.public.home') }}"
           class="w-full rounded-none bg-primary px-10 py-4 text-center text-sm font-bold uppercase tracking-widest text-white transition hover:bg-primary/90 sm:w-auto">
            Về trang chủ
        </a>
        <a href="{{ route('event.public.home') }}"
           class="w-full rounded-none border border-gray-900 px-10 py-4 text-center text-sm font-bold uppercase tracking-widest text-gray-900 transition hover:bg-gray-900 hover:text-white sm:w-auto">
            Xem sự kiện
        </a>
    </div>
    <p class="mt-6 text-center text-sm text-gray-500">
        Còn sự kiện khác?
        <a href="{{ route('event.public.submit.form') }}" class="font-medium text-primary underline-offset-4 hover:underline">Gửi thêm sự kiện</a>
    </p>

</div>
</div>
@endsection
