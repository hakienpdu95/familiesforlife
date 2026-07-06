@extends('layouts.backend')
@section('title', 'Thêm Bộ sản phẩm OCOP')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-base-content mb-5">Thêm Bộ sản phẩm OCOP</h1>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <form method="POST" action="{{ route('ocop_rubric.admin.product-groups.store') }}">
                @include('ocoprubric::admin.product-groups._form')
            </form>
        </div>
    </div>
</div>
@endsection
