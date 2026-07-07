@extends('layouts.backend')
@section('title', $article->title)

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-1">
        <h1 class="text-2xl font-bold text-base-content">{{ $article->title }}</h1>
        <span class="badge {{ $article->status->badgeClass() }}">{{ $article->status->label() }}</span>
    </div>
    <p class="text-sm text-base-content/50 mb-5">
        {{ $article->format->label() }} · Tạo bởi {{ $article->createdBy?->name ?? '—' }}
        @if($article->approvedBy) · Duyệt bởi {{ $article->approvedBy->name }} @endif
    </p>

    @if($article->categories->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mb-4">
        @foreach($article->categories as $cat)
        <span class="badge badge-sm {{ $cat->pivot->is_primary ? 'badge-primary' : 'badge-ghost' }}">{{ $cat->name }}</span>
        @endforeach
    </div>
    @endif

    @if($article->excerpt)
    <p class="text-base-content/70 italic mb-4">{{ $article->excerpt }}</p>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body prose max-w-none">
            {!! app(\Modules\Post\Support\ArticleContentRenderer::class)->render($article) !!}
        </div>
    </div>

    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mt-4">
        @foreach($article->tags as $tag)
        <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm">← Danh sách bài viết</a>
        @can('update', $article)
        <a href="{{ route('backend.post.articles.edit', $article) }}" class="btn btn-primary btn-sm">Sửa bài viết</a>
        @endcan
    </div>
</div>
@endsection
