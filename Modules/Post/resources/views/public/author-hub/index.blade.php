@extends('layouts.frontend')

@section('title', 'Tác giả')
@section('meta_description', 'Danh sách tác giả — phóng viên và cộng tác viên viết bài')

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li>Tác giả</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">Tác giả</h1>

    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($authors as $author)
        @php $profile = $author->authorProfile; @endphp
        <a href="{{ route('post.public.author-hub.show', $profile) }}"
           class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body items-center text-center p-5">
                <img src="{{ $profile->avatarUrl() }}" alt="{{ $profile->displayName() }}"
                     class="w-20 h-20 rounded-full object-cover mb-2">
                <p class="font-semibold text-base-content">{{ $profile->displayName() }}</p>
                <p class="text-xs text-base-content/50">{{ $author->published_articles_count }} bài đã xuất bản</p>
            </div>
        </a>
        @empty
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có tác giả nào công khai hồ sơ.</p>
        @endforelse
    </section>

    @if($authors->hasPages())
    <div class="pt-10 flex justify-center">
        {{ $authors->onEachSide(1)->links() }}
    </div>
    @endif

</div>
@endsection
