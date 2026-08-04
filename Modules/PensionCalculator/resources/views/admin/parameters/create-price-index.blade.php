@extends('layouts.backend')
@section('title', 'Thêm bảng hệ số trượt giá')

@section('content')
<div class="max-w-2xl" x-data="{ rows: [{ contribution_year: null, coefficient: null }] }">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Thêm bảng hệ số trượt giá</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Nhập theo LÔ 1 năm giải quyết (§9.2) — liệt kê hệ số của từng năm đã đóng trước đó, đúng cách BHXH Việt Nam công bố hàng năm.</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.pension-calculator.price-index.store') }}" class="card bg-base-100 shadow-sm border border-base-200">
        @csrf
        <div class="card-body py-4 px-4 space-y-3">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Năm giải quyết (settlement_year)</span></label>
                    <input type="number" name="settlement_year" value="{{ old('settlement_year') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Nguồn văn bản</span></label>
                    <input type="text" name="source_document" value="{{ old('source_document') }}" required placeholder="VD: Thông tư .../BLĐTBXH" class="input input-bordered input-sm">
                </div>
            </div>

            <div class="divider text-xs">Hệ số theo từng năm đã đóng</div>

            <template x-for="(row, idx) in rows" :key="idx">
                <div class="flex gap-2 items-end">
                    <div class="form-control flex-1">
                        <label class="label py-0.5"><span class="label-text text-xs">Năm đã đóng</span></label>
                        <input type="number" :name="`rows[${idx}][contribution_year]`" x-model.number="row.contribution_year" required class="input input-bordered input-sm">
                    </div>
                    <div class="form-control flex-1">
                        <label class="label py-0.5"><span class="label-text text-xs">Hệ số</span></label>
                        <input type="number" step="0.01" min="1" :name="`rows[${idx}][coefficient]`" x-model.number="row.coefficient" required class="input input-bordered input-sm">
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm text-error" @click="rows.length > 1 && rows.splice(idx, 1)">Xoá</button>
                </div>
            </template>

            <button type="button" class="btn btn-ghost btn-xs w-fit" @click="rows.push({ contribution_year: null, coefficient: null })">+ Thêm dòng</button>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('backend.pension-calculator.index') }}" class="btn btn-ghost btn-sm">Huỷ</a>
                <button type="submit" class="btn btn-primary btn-sm">Lưu bảng hệ số</button>
            </div>
        </div>
    </form>
</div>
@endsection
