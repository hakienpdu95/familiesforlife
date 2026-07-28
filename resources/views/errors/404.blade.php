@extends('layouts.frontend')

@section('title', 'Không tìm thấy trang')
@section('meta_description', 'Trang bạn tìm không tồn tại hoặc đã được di chuyển.')

@push('meta')
<meta name="robots" content="noindex, follow">
@endpush

@section('content')
<div class="container py-24 flex flex-col items-center text-center gap-4">
    <p class="text-6xl font-black text-primary">404</p>
    <h1 class="text-2xl font-bold text-base-content">Không tìm thấy trang</h1>
    <p class="text-base-content/60 max-w-md">
        Trang bạn tìm không tồn tại, đã bị xoá, hoặc đường dẫn không còn đúng nữa.
    </p>
    <div class="flex flex-wrap justify-center gap-3 mt-4">
        <a href="{{ route('post.public.home') }}" class="btn btn-primary">Về Trang Chủ</a>
        <a href="{{ route('post.public.author-hub.index') }}" class="btn btn-outline">Xem Tác Giả</a>
    </div>
</div>
@endsection
