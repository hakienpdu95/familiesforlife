@extends('layouts.backend')
@section('title', 'Import tác động AI từ CSV')

@section('content')

<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold">Import tác động AI từ CSV</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Nhập hàng loạt chỉ số từ file CSV</p>
    </div>
    <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Quay lại
    </a>
</div>

<div class="max-w-2xl space-y-5">

    {{-- Template download --}}
    <div class="alert alert-info py-3 px-4 text-sm gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="font-medium mb-1">Format file CSV (UTF-8, có header dòng đầu):</p>
            <code class="text-xs bg-base-100/50 px-2 py-0.5 rounded font-mono block mt-1 leading-relaxed">
                employee_code, impact_category, impact_type, period_start, period_end,<br>
                baseline_value, achieved_value, investment_cost, benefit_value, notes
            </code>
        </div>
    </div>

    {{-- Category reference --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-3">Giá trị hợp lệ cho impact_category</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                @foreach([
                    ['learning',     'Học tập',      'badge-info'],
                    ['productivity', 'Năng suất',    'badge-success'],
                    ['quality',      'Chất lượng',   'badge-accent'],
                    ['ai_adoption',  'Ứng dụng AI',  'badge-primary'],
                    ['business',     'Kinh doanh',   'badge-warning'],
                ] as [$val, $label, $cls])
                <div class="flex items-center gap-1.5">
                    <span class="badge {{ $cls }} badge-xs">{{ $label }}</span>
                    <code class="font-mono text-base-content/50">{{ $val }}</code>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Example --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-2">Ví dụ dữ liệu</p>
            <div class="overflow-x-auto">
                <table class="table table-xs font-mono text-xs">
                    <thead><tr class="text-base-content/40">
                        <th>employee_code</th><th>impact_category</th><th>impact_type</th>
                        <th>period_start</th><th>period_end</th><th>baseline_value</th>
                        <th>achieved_value</th><th>investment_cost</th><th>benefit_value</th><th>notes</th>
                    </tr></thead>
                    <tbody>
                        <tr><td>NV001</td><td>productivity</td><td>time_saving</td><td>2026-01-01</td><td>2026-03-31</td><td>120</td><td>45</td><td>5000000</td><td>18000000</td><td>Viết báo cáo nhanh hơn</td></tr>
                        <tr><td>NV002</td><td>quality</td><td>error_rate_reduction</td><td>2026-01-01</td><td>2026-03-31</td><td>8</td><td>2</td><td>0</td><td>0</td><td></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Upload form --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('info'))
            <div class="alert alert-warning mb-4 py-2 px-4 text-sm">{{ session('info') }}</div>
            @endif

            <form method="POST" action="{{ route('backend.ai-impact.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-control mb-5">
                    <label class="label py-1"><span class="label-text text-sm font-medium">Chọn file CSV <span class="text-error">*</span></span></label>
                    <input type="file" name="csv_file" accept=".csv,.txt"
                           class="file-input file-input-bordered file-input-sm w-full @error('csv_file') file-input-error @enderror">
                    <label class="label py-0.5"><span class="label-text-alt text-xs text-base-content/30">Tối đa 2 MB. Định dạng: .csv hoặc .txt (UTF-8)</span></label>
                    @error('csv_file')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import
                    </button>
                    <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
