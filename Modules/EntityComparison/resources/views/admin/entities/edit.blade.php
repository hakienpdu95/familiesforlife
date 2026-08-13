@extends('layouts.backend')
@section('title', 'Sửa đối tượng')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Sửa đối tượng — {{ $entity->name }}</h1>

<form action="{{ route('backend.entity_comparison.entities.update', $entity) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('entitycomparison::admin.entities._form')
</form>
@endsection
