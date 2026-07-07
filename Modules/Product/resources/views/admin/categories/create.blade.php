@extends('layouts.backend')
@section('title', 'Thêm danh mục sản phẩm')

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-base-content mb-5">Thêm danh mục sản phẩm</h1>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <form method="POST" action="{{ route('backend.products.categories.store') }}">
                @include('product::admin.categories._form')
            </form>
        </div>
    </div>
</div>
@endsection
