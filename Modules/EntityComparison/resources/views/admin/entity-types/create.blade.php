@extends('layouts.backend')
@section('title', 'Thêm loại đối tượng')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Thêm loại đối tượng</h1>

<form action="{{ route('backend.entity_comparison.entity_types.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @php($entityType = null)
    @include('entitycomparison::admin.entity-types._form')
</form>
@endsection
