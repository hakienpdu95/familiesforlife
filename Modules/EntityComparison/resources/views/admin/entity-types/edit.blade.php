@extends('layouts.backend')
@section('title', 'Sửa loại đối tượng')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Sửa loại đối tượng — {{ $entityType->name }}</h1>

<form action="{{ route('backend.entity_comparison.entity_types.update', $entityType) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('entitycomparison::admin.entity-types._form')
</form>
@endsection
