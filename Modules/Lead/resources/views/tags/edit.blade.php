@extends('layouts.backend')
@section('title', 'Sửa tag: ' . $tag->name)


@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa tag</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Chỉnh sửa tên và màu sắc tag</p>
    </div>
    <a href="{{ route('lead.tags.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ route('lead.tags.update', $tag) }}"
      novalidate data-tag-form>
    @csrf
    @method('PUT')

    <div class="max-w-sm">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                @include('lead::tags._form')

                <div class="flex gap-2 pt-2 border-t border-base-200 mt-1">
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Lưu thay đổi
                    </button>
                    <a href="{{ route('lead.tags.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                </div>
            </div>
        </div>
    </div>

</form>
@endsection
