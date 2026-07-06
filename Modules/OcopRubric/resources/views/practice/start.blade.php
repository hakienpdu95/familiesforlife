@extends('layouts.backend')
@section('title', 'Luyện tập OCOP')

@section('content')
<div class="max-w-xl">
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <h1 class="text-2xl font-bold text-base-content mb-2">Luyện tập chấm điểm OCOP</h1>
    <p class="text-sm text-base-content/50 mb-5">
        Chọn 1 sản phẩm để bắt đầu luyện tập — không giới hạn số lần làm lại, không ảnh hưởng hồ sơ chính thức.
    </p>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            @if ($products->isEmpty())
            <p class="text-sm text-base-content/50">
                Tổ chức bạn chưa có sản phẩm OCOP nào.
                <a href="{{ route('ocop.products.create') }}" class="link link-primary">Đăng ký sản phẩm đầu tiên</a>.
            </p>
            @else
            <form method="POST" action="{{ route('ocop.practice.create') }}" class="flex flex-col gap-4">
                @csrf
                <div class="form-control">
                    <label class="label label-text text-xs">Sản phẩm</label>
                    <select name="product_id" class="select select-bordered select-sm" required>
                        <option value="">— Chọn sản phẩm —</option>
                        @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->productGroup?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Bắt đầu luyện tập</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
