@extends('layouts.backend')
@section('title', 'Sửa sản phẩm OCOP')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa sản phẩm OCOP</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $product->name }}</p>
    </div>
    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.ocop.products.update', $product) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('ocop::admin.products._form', ['product' => $product])
</form>

@endsection
