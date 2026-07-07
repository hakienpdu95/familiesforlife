@extends('layouts.backend')
@section('title', 'Sửa bài viết')

@section('content')

@foreach(['success','error'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition.opacity.duration.500ms
         class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
        <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
    </div>
    @endif
@endforeach

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-base-content">Sửa bài viết</h1>
        <span class="badge {{ $article->status->badgeClass() }}">{{ $article->status->label() }}</span>
    </div>

    <div class="flex items-center gap-2" x-data="{ showSchedule: false }">
        @can('submitForReview', $article)
        @if(in_array($article->status, [\Modules\Post\Enums\ArticleStatus::Draft], true))
        <form method="POST" action="{{ route('backend.post.articles.submit', $article) }}">
            @csrf
            <button class="btn btn-sm btn-outline">Gửi duyệt</button>
        </form>
        @endif
        @endcan

        @can('publish', $article)
        @if($article->status !== \Modules\Post\Enums\ArticleStatus::Published)
        <form method="POST" action="{{ route('backend.post.articles.publish', $article) }}">
            @csrf
            <button class="btn btn-sm btn-success">Xuất bản ngay</button>
        </form>
        @endif

        <button type="button" class="btn btn-sm btn-info" @click="showSchedule = !showSchedule">Lên lịch</button>

        @if($article->status !== \Modules\Post\Enums\ArticleStatus::Archived)
        <form method="POST" action="{{ route('backend.post.articles.archive', $article) }}"
              onsubmit="return confirm('Lưu trữ bài viết này?')">
            @csrf
            <button class="btn btn-sm btn-ghost">Lưu trữ</button>
        </form>
        @endif
        @endcan

        <div x-show="showSchedule" x-cloak class="absolute mt-10 right-6 z-20 card bg-base-100 shadow-lg border border-base-200 p-4">
            <form method="POST" action="{{ route('backend.post.articles.schedule', $article) }}" class="flex items-end gap-2">
                @csrf
                <div class="form-control">
                    <label class="label label-text text-xs">Xuất bản lúc</label>
                    <input type="datetime-local" name="published_at" class="input input-bordered input-sm" required>
                </div>
                <button class="btn btn-sm btn-primary">Lên lịch</button>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('backend.post.articles.update', $article) }}">
    @include('post::admin.articles._form')
</form>
@endsection

@push('styles')
@vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
@vite(['resources/js/modules/jodit.js', 'Modules/Post/resources/assets/js/post.js'], 'build/backend')
@endpush
