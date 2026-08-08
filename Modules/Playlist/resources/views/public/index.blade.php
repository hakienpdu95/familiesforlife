{{-- spec/Playlist_Technical_Specification.md §7.1 — lưới danh sách playlist active. --}}
@extends('layouts.frontend')

@section('title', 'Playlist')

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-6">Playlist</h1>

    @if($playlists->isEmpty())
        <div class="text-center py-16 text-base-content/60">
            <p>Chưa có playlist nào được đăng.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($playlists as $playlist)
            <a href="{{ route('playlist.public.show', $playlist) }}"
               class="rounded-lg overflow-hidden border border-base-300 hover:shadow-lg transition block">
                <img src="{{ $playlist->effective_cover_image_url ?? asset('images/post-cover-placeholder.svg') }}"
                     alt="{{ $playlist->name }}" loading="lazy"
                     onerror="this.onerror=null; this.src='{{ asset('images/post-cover-placeholder.svg') }}';"
                     class="w-full aspect-video object-cover">
                <div class="p-4">
                    <h3 class="font-semibold">{{ $playlist->name }}</h3>
                    @if($playlist->description)
                    <p class="text-sm text-base-content/70 mt-1">{{ Str::limit($playlist->description, 120) }}</p>
                    @endif
                    <p class="text-xs text-base-content/40 mt-2">{{ $playlist->visible_itemables->count() }} nội dung</p>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $playlists->links() }}</div>
    @endif
</div>
@endsection
