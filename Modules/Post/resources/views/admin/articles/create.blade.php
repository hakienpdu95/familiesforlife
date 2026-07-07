@extends('layouts.backend')
@section('title', 'Thêm bài viết')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Thêm bài viết</h1>

<form method="POST" action="{{ route('backend.post.articles.store') }}">
    @include('post::admin.articles._form')
</form>
@endsection

@push('styles')
@vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
@vite(['resources/js/modules/jodit.js', 'Modules/Post/resources/assets/js/post.js'], 'build/backend')
@endpush
