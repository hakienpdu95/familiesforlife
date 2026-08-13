@extends('layouts.backend')
@section('title', 'Sửa tiêu chí')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Sửa tiêu chí — {{ $criterion->name }}</h1>

<form action="{{ route('backend.entity_comparison.criteria.update', $criterion) }}" method="POST">
    @csrf
    @method('PUT')
    @include('entitycomparison::admin.criteria._form')
</form>
@endsection
