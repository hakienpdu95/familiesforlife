@extends('layouts.backend')
@section('title', 'Sửa sản phẩm OCOP')

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-base-content mb-5">Sửa sản phẩm: {{ $product->name }}</h1>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <form method="POST" action="{{ route('ocop.products.update', $product) }}">
                @include('ocoprubric::products._form')
            </form>
        </div>
    </div>
</div>
@endsection
