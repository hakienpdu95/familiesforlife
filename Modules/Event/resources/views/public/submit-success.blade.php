@extends('layouts.frontend')

@section('title', 'Đã Gửi Sự Kiện')

@section('content')
<section class="relative overflow-hidden bg-warning/15 py-20 text-center">
    <div class="blob bg-warning h-24 w-24 -left-6 top-6"></div>
    <div class="blob bg-accent h-16 w-16 left-24 bottom-4"></div>
    <div class="blob bg-primary h-14 w-14 right-16 top-10"></div>
    <div class="blob bg-secondary h-20 w-20 -right-8 bottom-0"></div>

    <div class="relative max-w-lg mx-auto px-4">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary text-primary-content mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="font-black text-2xl text-secondary">Cảm Ơn Bạn!</h1>
        <p class="mt-3 text-sm text-base-content/70">
            Sự kiện của bạn đã được gửi thành công và đang chờ đội ngũ biên tập xem xét.
            Chúng tôi sẽ gửi email thông báo kết quả trong thời gian sớm nhất.
        </p>
        <a href="{{ route('post.public.home') }}" class="btn mt-6 border-none bg-secondary text-white hover:bg-secondary/90 px-8">Về Trang Chủ</a>
    </div>
</section>
@endsection
