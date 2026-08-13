@extends('layouts.backend')
@section('title', 'Thêm đối tượng')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-5">Thêm đối tượng</h1>

<form action="{{ route('backend.entity_comparison.entities.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @php($entity = null)
    @include('entitycomparison::admin.entities._form')
</form>
@endsection
