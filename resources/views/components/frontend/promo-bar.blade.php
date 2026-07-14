@props([
    'categories', // Collection<PostCategory> — lấy tối đa 3 danh mục gốc đầu tiên
    'locale',
])

@php
    $palette = ['bg-secondary', 'bg-primary', 'bg-warning'];
@endphp

@if($categories->isNotEmpty())
<div class="flex text-white text-sm font-black uppercase tracking-wide text-center">
    @foreach($categories->take(3) as $i => $cat)
    <a href="{{ route('post.public.category', ['locale' => $locale, 'category' => $cat->slug]) }}"
       class="flex-1 py-4 {{ $palette[$i % count($palette)] }} hover:opacity-90">{{ $cat->name }}</a>
    @endforeach
</div>
@endif
