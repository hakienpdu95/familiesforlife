@extends('layouts.backend')
@section('title', 'Thêm sản phẩm')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Thêm sản phẩm</h1>

<form method="POST" action="{{ route('backend.products.store') }}">
    @include('product::admin.products._form')
</form>
@endsection
