@extends('layouts.backend')
@section('title', 'Tạo project — AI Video Studio')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo project mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Đặt tên chủ đề (VD: "Review bỉm") — thêm shot sau khi tạo</p>
    </div>
    <a href="{{ route('backend.aivideostudiotemplate.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.aivideostudiotemplate.store') }}" novalidate>
    @csrf
    @include('aivideostudiotemplate::_form', ['project' => null])
</form>

@endsection
