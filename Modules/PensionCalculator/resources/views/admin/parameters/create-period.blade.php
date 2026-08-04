@extends('layouts.backend')
@section('title', 'Thêm giai đoạn tham số')

@section('content')
<div class="max-w-2xl">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Thêm giai đoạn hiệu lực tham số mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Bất biến sau khi lưu — không sửa/xoá được (§9.1).</p>
    </div>

    @if($latestPeriod)
    <div class="alert alert-info text-sm mb-4">
        Giai đoạn gần nhất hiện có: <strong>{{ $latestPeriod->effective_from->format('d/m/Y') }}</strong> — ngày hiệu lực mới phải sau ngày này.
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.pension-calculator.periods.store') }}" class="card bg-base-100 shadow-sm border border-base-200">
        @csrf
        <div class="card-body py-4 px-4 space-y-3">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Hiệu lực từ ngày</span></label>
                    <input type="date" name="effective_from" value="{{ old('effective_from') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tỷ lệ đóng (%)</span></label>
                    <input type="number" step="0.01" name="contribution_rate_percent" value="{{ old('contribution_rate_percent', 22) }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Mức chuẩn hộ nghèo nông thôn (CN, đ)</span></label>
                    <input type="number" step="1000" name="rural_poverty_line" value="{{ old('rural_poverty_line') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Mức tham chiếu (đ)</span></label>
                    <input type="number" step="1000" name="reference_level" value="{{ old('reference_level') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Hệ số trần đóng (× mức tham chiếu)</span></label>
                    <input type="number" name="ceiling_multiplier" value="{{ old('ceiling_multiplier', 20) }}" required class="input input-bordered input-sm">
                </div>
            </div>

            <div class="divider text-xs">Tỷ lệ hỗ trợ nhà nước theo nhóm (%)</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($supportGroups as $group)
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">{{ $group->label() }}</span></label>
                    <input type="number" step="0.01" name="support_tiers[{{ $group->value }}]"
                           value="{{ old('support_tiers.'.$group->value, match($group->value) { 'poor_household' => 50, 'near_poor_household' => 40, 'ethnic_minority' => 30, default => 20 }) }}"
                           required class="input input-bordered input-sm">
                </div>
                @endforeach
            </div>

            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Nguồn văn bản</span></label>
                <input type="text" name="source_document" value="{{ old('source_document') }}" required placeholder="VD: Nghị định 159/2025/NĐ-CP Điều 5" class="input input-bordered input-sm">
            </div>
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Ghi chú (tuỳ chọn)</span></label>
                <textarea name="notes" rows="2" class="textarea textarea-bordered textarea-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('backend.pension-calculator.index') }}" class="btn btn-ghost btn-sm">Huỷ</a>
                <button type="submit" class="btn btn-primary btn-sm">Lưu giai đoạn</button>
            </div>
        </div>
    </form>
</div>
@endsection
