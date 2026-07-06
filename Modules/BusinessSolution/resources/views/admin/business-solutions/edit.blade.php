@extends('layouts.backend')
@section('title', 'Sửa Business Solution')

@section('content')
<div class="max-w-2xl">
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <h1 class="text-2xl font-bold text-base-content mb-5">Sửa Business Solution: {{ $solution->name }}</h1>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <form method="POST" action="{{ route('business_solutions.admin.update', $solution) }}">
                @include('businesssolution::admin.business-solutions._form')
            </form>
        </div>
    </div>
</div>
@endsection
