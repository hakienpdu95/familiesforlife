@extends('layouts.frontend')

@section('title', $site->name)
@section('meta_description', \Illuminate\Support\Str::limit($site->description ?: $site->name, 160))

@section('content')
<div class="container">

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <div class="aspect-[21/9] rounded-md overflow-hidden bg-base-200 mb-3">
        <img src="{{ $site->getFirstMediaUrl('cover') ? $site->getFirstMediaUrl('cover', 'preview') : asset('images/post-cover-placeholder.svg') }}"
             alt="{{ $site->name }}" class="h-full w-full object-cover">
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-3">
        <span class="badge badge-primary">{{ $site->heritage_type->label() }}</span>
        <span class="badge badge-ghost">{{ $site->rank->label() }}</span>
    </div>

    <article class="detail-wrap">
        <header class="detail__header">
            @php
                $authorProfile = $site->createdBy?->authorProfile;
                $authorIsLinkable = $authorProfile?->is_public
                    && \Modules\Post\Features\AuthorHub\Support\AuthorRoleResolver::isEligible($site->createdBy);
            @endphp
            <h1 id="btn_exp_edit" class="detail__title"> {{ $site->name }} </h1>
            <p class="mt-2 text-sm text-base-content/60">
                {{ trim(collect([$site->address, $site->ward_name, $site->province_name])->filter()->implode(', '), ', ') ?: 'Chưa cập nhật địa chỉ' }}
                @if($site->era) &middot; Niên đại: {{ $site->era }} @endif
            </p>
            <div class="detail__tools print-hide">
                <div class="detail__author">
                    @if($authorIsLinkable)
                        <a href="{{ route('post.public.author-hub.show', $authorProfile) }}" class="hover:underline">{{ $authorProfile->displayName() }}</a>
                    @else
                        <strong>{{ $site->createdBy?->name ?? 'Ban biên tập' }}</strong>
                    @endif
                </div>
                <div class="detail__share">
                    <iframe title="Like share" src="https://www.facebook.com/plugins/like.php?href={{ urlencode(url()->current()) }}&amp;width=175&amp;layout=button_count&amp;action=like&amp;size=small&amp;share=true&amp;height=35" width="138" height="20" style="border: none; overflow: hidden;width: 138px;" scrolling="no" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>
                </div>
            </div>
            @if($site->description)
            <h2 class="detail__summary" style="text-align: justify;">{{ $site->description }}</h2>
            @endif
        </header>    

        <div class="detail__footer print-hide">
            <section class="zone"></section>
        </div>
    </article>

    @if($articles->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-base-200">
        <h2 class="text-xl font-bold text-base-content mb-4">Bài viết liên quan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($articles as $article)
            @php($t = $article->mainTranslation())
            @if($t)
            <a href="{{ route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]) }}" class="group flex flex-col gap-2">
                <div class="aspect-[16/9] overflow-hidden bg-base-200 rounded-md">
                    <img src="{{ $article->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $t->title }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <h3 class="font-bold text-sm leading-snug group-hover:text-primary line-clamp-2">{{ $t->title }}</h3>
            </a>
            @endif
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Lễ hội sắp diễn ra ───────────────────────────────────────────── --}}
    @if($events->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-base-200">
        <h2 class="text-xl font-bold text-base-content mb-4">Lễ hội sắp diễn ra</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($events as $event)
            <a href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}" class="group flex flex-col gap-2">
                <div class="aspect-[16/9] overflow-hidden bg-base-200 rounded-md">
                    <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <h3 class="font-bold text-sm leading-snug group-hover:text-primary line-clamp-2">{{ $event->title }}</h3>
                <p class="text-xs text-base-content/50">{{ $event->start_date?->format('d/m/Y') }}</p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Sản phẩm OCOP của làng nghề này ─────────────────────────────── --}}
    @if($products->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-base-200">
        <h2 class="text-xl font-bold text-base-content mb-4">Sản phẩm OCOP của làng nghề này</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5">
            @foreach($products as $product)
            <a href="{{ route('ocop.public.show', ['slug' => $product->slug, 'id' => $product->id]) }}" class="group flex flex-col gap-2">
                <div class="aspect-square rounded-sm overflow-hidden bg-base-200">
                    <img src="{{ $product->imageUrl('medium') ?: asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <h3 class="text-sm font-bold leading-snug group-hover:text-primary line-clamp-2">{{ $product->name }}</h3>
            </a>
            @endforeach
        </div>
    </section>
    @endif

</div>
@endsection
