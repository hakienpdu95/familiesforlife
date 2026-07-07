@extends('layouts.backend')
@section('title', 'Sửa danh mục bài viết')

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-base-content mb-5">Sửa danh mục "{{ $category->name }}"</h1>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <form method="POST" action="{{ route('backend.post.categories.update', $category) }}">
                @include('post::admin.categories._form')
            </form>
        </div>
    </div>
</div>
@endsection
