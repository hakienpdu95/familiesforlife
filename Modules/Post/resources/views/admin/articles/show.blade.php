@extends('layouts.backend')
@section('title', $article->mainTranslation()?->title ?? 'Bài viết')

@section('content')
<div class="max-w-3xl">

    @php($main = $article->mainTranslation())

    <div class="flex items-center gap-3 mb-1">
        <h1 class="text-2xl font-bold text-base-content">{{ $main?->title ?? '(chưa có bản dịch chính)' }}</h1>
        @if($main)
        <span class="badge {{ $main->status->badgeClass() }}">{{ $main->status->label() }}</span>
        @endif
    </div>
    <p class="text-sm text-base-content/50 mb-5">
        {{ $article->format->label() }} · Tạo bởi {{ $article->createdBy?->name ?? '—' }}
        @if($main?->approvedBy) · Duyệt bởi {{ $main->approvedBy->name }} @endif
    </p>

    @if($article->categories->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mb-4">
        @foreach($article->categories as $cat)
        <span class="badge badge-sm {{ $cat->pivot->is_primary ? 'badge-primary' : 'badge-ghost' }}">{{ $cat->name }}</span>
        @endforeach
    </div>
    @endif

    @if($main?->excerpt)
    <p class="text-base-content/70 italic mb-4">{{ $main->excerpt }}</p>
    @endif

    @if($main)
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body prose max-w-none">
            {!! app(\Modules\Post\Support\ArticleContentRenderer::class)->render($main) !!}
        </div>
    </div>
    @else
    <div class="alert alert-warning text-sm">Bài viết chưa có bản dịch nào cho ngôn ngữ chính ({{ $article->main_locale }}).</div>
    @endif

    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mt-4">
        @foreach($article->tags as $tag)
        <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

    @if($article->translations->count() > 1)
    <div class="mt-6">
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Các bản dịch khác</p>
        <div class="flex flex-wrap gap-2">
            @foreach($article->translations as $t)
            <a href="{{ route('backend.post.articles.edit', $article) }}?locale={{ $t->locale }}"
               class="badge badge-lg gap-1.5 {{ $t->status->badgeClass() }}">
                {{ config('post.locales')[$t->locale] ?? $t->locale }} · {{ $t->status->label() }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm">← Danh sách bài viết</a>
        <a href="{{ route('backend.post.articles.edit', $article) }}" class="btn btn-primary btn-sm">Sửa bài viết</a>
    </div>
</div>
@endsection
