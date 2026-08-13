@extends('layouts.backend')
@section('title', 'Thêm tiêu chí')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Thêm tiêu chí so sánh</h1>

<form action="{{ route('backend.entity_comparison.criteria.store') }}" method="POST">
    @csrf
    @php($criterion = null)
    @include('entitycomparison::admin.criteria._form')
</form>
@endsection
