@extends('layouts.frontend')

@section('title', $authorProfile->displayName())
@section('meta_description', $authorProfile->bio ?: $authorProfile->displayName())

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li><a href="{{ route('post.public.author-hub.index') }}">Tác giả</a></li>
            <li>{{ $authorProfile->displayName() }}</li>
        </ul>
    </div>

    {{-- ── Header — avatar to, tên, bio, mạng xã hội (§7.3). KHÔNG hiển thị số liệu hiệu
         suất (view_count) — chỉ số lượng bài đã xuất bản (§0). ─────────────────────── --}}
    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 mb-10 text-center sm:text-left">
        <img src="{{ $authorProfile->avatarUrl() }}" alt="{{ $authorProfile->displayName() }}"
             class="w-28 h-28 rounded-full object-cover shrink-0">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content">{{ $authorProfile->displayName() }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $articles->total() }} bài đã xuất bản</p>

            @if($authorProfile->bio)
            <p class="text-base-content/70 mt-3 max-w-2xl">{{ $authorProfile->bio }}</p>
            @endif

            @if(!empty($authorProfile->social_links))
            <div class="flex items-center justify-center sm:justify-start gap-2 mt-3">
                @foreach(['facebook' => 'Facebook', 'x' => 'X', 'linkedin' => 'LinkedIn', 'website' => 'Website'] as $key => $label)
                    @if(!empty($authorProfile->social_links[$key]))
                    <a href="{{ $authorProfile->social_links[$key] }}" target="_blank" rel="noopener nofollow"
                       class="badge badge-outline hover:badge-primary">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <h2 class="text-lg font-semibold text-base-content mb-4">Bài đã xuất bản</h2>

    <x-frontend.article-grid :articles="$articles" />

</div>
@endsection
