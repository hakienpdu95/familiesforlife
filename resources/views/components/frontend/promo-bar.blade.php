@props([
    'categories', // Collection<PostCategory> — lấy tối đa 3 danh mục gốc đầu tiên
    'provinces' => collect(),
])

@php
    $palette = ['bg-secondary', 'bg-primary', 'bg-warning'];
@endphp

@if($provinces->isNotEmpty())
<nav aria-label="Tỉnh/thành nổi bật" class="flex flex-wrap sm:flex-nowrap text-sm font-medium uppercase tracking-wide text-center">
    @foreach($provinces as $province)
    <a href="{{ route('province.public.show', ['type' => $province->place_type, 'slug' => $province->slug]) }}"
       style="{{ \Modules\ProvinceShowcase\Support\ProvinceTabColor::style($province->theme_color) }}"
       class="flex-1 basis-1/2 sm:basis-0 py-4 px-2 hover:opacity-90">{{ $province->short_name ?: $province->name }}</a>
    @endforeach
</nav>
@elseif($categories->isNotEmpty())
<div class="flex text-white text-sm font-black uppercase tracking-wide text-center">
    @foreach($categories->take(3) as $i => $cat)
    <a href="{{ route('post.public.category', ['category' => $cat->slug]) }}"
       class="flex-1 py-4 {{ $palette[$i % count($palette)] }} hover:opacity-90">{{ $cat->name }}</a>
    @endforeach
</div>
@endif
