@extends('layouts.backend')
@section('title', 'Thêm bậc tỷ lệ hưởng lương hưu')

@section('content')
<div class="max-w-2xl">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Thêm bậc tỷ lệ hưởng lương hưu</h1>
        <p class="text-sm text-base-content/50 mt-0.5">spec §14 mục 1 — chỉ nhập khi đã đối chiếu trực tiếp với Điều 66/99 Luật Bảo hiểm xã hội 2024 thật. KHÔNG nhập số liệu suy đoán/phổ thông chưa xác minh.</p>
    </div>

    <div class="alert alert-warning text-sm mb-4">
        Trước khi lưu, hãy chắc chắn số liệu dưới đây lấy trực tiếp từ văn bản Luật Bảo hiểm xã hội 2024 (Điều 66 cho nhánh mixed, Điều 99 cho nhánh thuần tự nguyện trước 2021) — không phải suy đoán từ cấu trúc phổ thông "45% nền + 2%/năm, trần 75%" (spec §0/§14 mục 1).
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.pension-calculator.rate-brackets.store') }}" class="card bg-base-100 shadow-sm border border-base-200">
        @csrf
        <div class="card-body py-4 px-4 space-y-3">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Giới tính</span></label>
                    <select name="gender" required class="select select-bordered select-sm">
                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Nam</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Nữ</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Số năm đóng tối thiểu áp dụng tỷ lệ nền</span></label>
                    <input type="number" name="min_years_for_base_rate" value="{{ old('min_years_for_base_rate') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tỷ lệ nền (%)</span></label>
                    <input type="number" step="0.01" name="base_rate_percent" value="{{ old('base_rate_percent') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tỷ lệ tăng thêm mỗi năm tiếp theo (%)</span></label>
                    <input type="number" step="0.01" name="increment_percent_per_year" value="{{ old('increment_percent_per_year') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tỷ lệ trần (%)</span></label>
                    <input type="number" step="0.01" name="max_rate_percent" value="{{ old('max_rate_percent', 75) }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Hiệu lực từ ngày</span></label>
                    <input type="date" name="effective_from" value="{{ old('effective_from') }}" required class="input input-bordered input-sm">
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Nguồn văn bản</span></label>
                <input type="text" name="source_document" value="{{ old('source_document') }}" required placeholder="VD: Luật Bảo hiểm xã hội 2024 Điều 66" class="input input-bordered input-sm">
            </div>
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Ghi chú (tuỳ chọn)</span></label>
                <textarea name="notes" rows="2" class="textarea textarea-bordered textarea-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('backend.pension-calculator.index') }}" class="btn btn-ghost btn-sm">Huỷ</a>
                <button type="submit" class="btn btn-primary btn-sm">Lưu bậc tỷ lệ</button>
            </div>
        </div>
    </form>
</div>
@endsection
