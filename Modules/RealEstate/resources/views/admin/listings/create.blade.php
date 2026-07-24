@extends('layouts.backend')
@section('title', 'Đăng tin bất động sản')

@section('content')
<div class="max-w-3xl">

    <div class="flex items-center gap-2 text-sm text-base-content/50 mb-6">
        <a href="{{ route('backend.real-estate.index') }}" class="hover:text-primary">Bất động sản</a>
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span>Đăng tin mới</span>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">Đăng tin bất động sản</h1>

    <form method="POST" action="{{ route('backend.real-estate.store') }}">
        @csrf
        @include('realestate::admin.listings._form', ['listing' => null])

        <div class="flex justify-end gap-2 mt-6">
            <a href="{{ route('backend.real-estate.index') }}" class="btn btn-ghost">Hủy</a>
            <button type="submit" class="btn btn-primary">Tạo tin (bản nháp)</button>
        </div>
    </form>

</div>
@endsection
