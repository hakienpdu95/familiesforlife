@extends('layouts.frontend')

@section('title', 'Xác nhận đăng ký bản tin')

@section('content')
<div class="container py-16 max-w-lg mx-auto text-center">
    @if($confirmed)
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-base-content mb-2">Đã xác nhận đăng ký</h1>
        <p class="text-base-content/60">Cảm ơn bạn — từ nay bạn sẽ nhận được bản tin của chúng tôi qua email này.</p>
    @else
        <div class="text-5xl mb-4">👍</div>
        <h1 class="text-2xl font-bold text-base-content mb-2">Bạn đã xác nhận trước đó</h1>
        <p class="text-base-content/60">Không cần thao tác gì thêm.</p>
    @endif
    <a href="{{ route('post.public.home') }}" class="btn btn-primary btn-sm mt-6">Về trang chủ</a>
</div>
@endsection
