@extends('layouts.backend')
@section('title', 'Sửa sản phẩm')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-base-content">Sửa sản phẩm "{{ $product->name }}"</h1>

    @can('update', $product)
    <form method="POST" action="{{ route('backend.products.change-status', $product) }}" class="flex items-center gap-2">
        @csrf
        <select name="status" class="select select-bordered select-sm" onchange="this.form.submit()">
            @foreach(\Modules\Product\Enums\ProductStatus::cases() as $s)
            <option value="{{ $s->value }}" @selected($product->status->value === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </form>
    @endcan
</div>

<form method="POST" action="{{ route('backend.products.update', $product) }}">
    @include('product::admin.products._form')
</form>
@endsection
