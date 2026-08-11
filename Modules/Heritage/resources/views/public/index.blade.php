@extends('layouts.frontend')

@section('title', 'Di sản & Văn hóa')
@section('meta_description', 'Di tích, di sản văn hóa có cấu trúc — loại hình, xếp hạng, toạ độ — cùng bài viết, lễ hội và sản phẩm OCOP liên quan.')

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li>Di sản & Văn hóa</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">Di sản & Văn hóa</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($sites as $site)
        <a href="{{ route('heritage.public.show', ['slug' => $site->slug, 'id' => $site->id]) }}" class="group flex flex-col gap-3">
            <div class="aspect-[4/3] rounded-xl overflow-hidden bg-base-200">
                <img src="{{ $site->getFirstMediaUrl('cover') ? $site->getFirstMediaUrl('cover', 'medium') : asset('images/post-cover-placeholder.svg') }}"
                     alt="{{ $site->name }}" class="h-full w-full object-cover" loading="lazy">
            </div>
            <div>
                <h3 class="font-bold leading-snug group-hover:text-primary mt-1">{{ $site->name }}</h3>
                <p class="mt-1 text-xs text-base-content/60">
                    <span class="badge badge-sm badge-ghost">{{ $site->heritage_type->label() }}</span>
                    <span class="badge badge-sm badge-ghost">{{ $site->rank->label() }}</span>
                </p>
                <p class="mt-1 text-xs text-base-content/50">{{ $site->province_name ?? 'Chưa rõ địa phương' }}</p>
            </div>
        </a>
        @empty
        <p class="col-span-full text-center py-10 text-base-content/40">Chưa có di tích nào.</p>
        @endforelse
    </div>

    @if($sites->hasPages())
    <div class="mt-8">{{ $sites->onEachSide(1)->links() }}</div>
    @endif

</div>
@endsection
