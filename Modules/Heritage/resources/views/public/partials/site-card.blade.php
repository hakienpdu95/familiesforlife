{{-- Thẻ di tích cho trang /di-san — cùng markup/class với <x-frontend.article-card> (size lg|sm)
     để bố cục đồng nhất trang danh mục bài viết (danh-muc/{slug}). --}}
@php
    $url = route('heritage.public.show', ['slug' => $site->slug, 'id' => $site->id]);
    $cover = $site->getFirstMediaUrl('cover')
        ? $site->getFirstMediaUrl('cover', $size === 'lg' ? 'preview' : 'medium')
        : asset('images/post-cover-placeholder.svg');
    $place = collect([$site->ward_name, $showProvince ? $site->province_name : null])->filter()->implode(', ');
@endphp

@if($size === 'lg')
<a href="{{ $url }}" class="group grid grid-cols-1 sm:grid-cols-5 sm:items-stretch overflow-hidden bg-base-100 border border-base-300">
    <div class="sm:col-span-3 aspect-[16/10] sm:aspect-auto bg-base-200">
        <img src="{{ $cover }}" alt="{{ $site->name }}" class="h-full w-full object-cover" loading="lazy">
    </div>
    <div class="sm:col-span-2 flex flex-col justify-center p-6 sm:p-10">
        <span class="text-xs font-black uppercase tracking-[0.2em] text-primary">{{ $site->heritage_type->label() }}</span>
        <h3 class="mt-3 text-2xl sm:text-3xl font-medium leading-snug group-hover:text-primary">{{ $site->name }}</h3>
        @if($site->description)
        <p class="mt-3 text-sm text-base-content/60 line-clamp-3">{{ $site->description }}</p>
        @endif
        <p class="mt-4 text-sm font-bold text-secondary">
            {{ $site->rank->label() }}@if($place) · {{ $place }}@endif
        </p>
    </div>
</a>
@else
<a href="{{ $url }}" class="group flex flex-col gap-3">
    <div class="aspect-[4/3] overflow-hidden bg-base-200">
        <img src="{{ $cover }}" alt="{{ $site->name }}" class="h-full w-full object-cover" loading="lazy">
    </div>
    <div>
        <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $site->heritage_type->label() }}</span>
        <h3 class="font-bold text-sm leading-snug group-hover:text-primary mt-1">{{ $site->name }}</h3>
        <p class="mt-1 text-xs font-semibold text-secondary">
            {{ $site->rank->label() }}@if($place) · {{ $place }}@endif
        </p>
    </div>
</a>
@endif
