@extends('layouts.backend')
@section('title', 'Tham số BHXH tự nguyện')

@section('content')
<div>

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Tham số BHXH tự nguyện</h1>
            <p class="text-sm text-base-content/50 mt-0.5">spec/bhxh/PensionCalculator_Technical_Specification.md §9 — mọi giai đoạn đã tạo là BẤT BIẾN, không có chỉnh sửa/xoá.</p>
        </div>
        <a href="{{ route('pension-calculator.public.index') }}" target="_blank" class="btn btn-ghost btn-sm">Xem trang công khai ↗</a>
    </div>

    {{-- Bài toán #27 (spec/giadinh.md — Quyết định 1193/QĐ-UBND, "hệ thống phân tích và dự báo
         nhu cầu an sinh xã hội") — tóm tắt thô từ dữ liệu ẩn danh, opt-in (pension_usage_logs).
         0 dòng là bình thường (chưa ai bật đóng góp) — không phải lỗi. --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body py-4 px-4">
            <h2 class="font-semibold text-base-content mb-1">Nhu cầu an sinh xã hội — thống kê ẩn danh (opt-in)</h2>
            <p class="text-xs text-base-content/50 mb-3">Chỉ tính trên lượt người dùng TỰ NGUYỆN bấm "Đóng góp dữ liệu ẩn danh" ở cuối Bước 5 trang công khai — không đại diện cho toàn bộ người dùng công cụ.</p>
            <div class="stats stats-vertical sm:stats-horizontal shadow-sm border border-base-200 w-full">
                <div class="stat py-3 px-4">
                    <div class="stat-title text-xs">Tổng lượt đóng góp</div>
                    <div class="stat-value text-lg">{{ number_format($usageStats['total']) }}</div>
                </div>
                <div class="stat py-3 px-4">
                    <div class="stat-title text-xs">Đã đủ điều kiện năm đóng</div>
                    <div class="stat-value text-lg">{{ $usageStats['total'] > 0 ? number_format($usageStats['eligible_count'] / $usageStats['total'] * 100, 1) : '—' }}%</div>
                </div>
                <div class="stat py-3 px-4">
                    <div class="stat-title text-xs">Trung bình còn thiếu (nhóm chưa đủ)</div>
                    <div class="stat-value text-lg">{{ $usageStats['avg_years_short'] !== null ? $usageStats['avg_years_short'].' năm' : '—' }}</div>
                </div>
            </div>
            @if($usageStats['branch_counts'])
            <p class="text-xs text-base-content/50 mt-2">Theo nhánh điều kiện (a/b/c/d, xem Bước 4 trang công khai):
                @foreach($usageStats['branch_counts'] as $branch => $count)
                    {{ $branch }}: {{ $count }}{{ !$loop->last ? ', ' : '' }}
                @endforeach
            </p>
            @endif
        </div>
    </div>

    {{-- ── Giai đoạn tham số ─────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body py-4 px-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-base-content">Giai đoạn hiệu lực tham số</h2>
                @can(\App\Enums\PermissionEnum::PENSION_CALCULATOR_MANAGE->value)
                <a href="{{ route('backend.pension-calculator.periods.create') }}" class="btn btn-primary btn-xs">+ Thêm giai đoạn</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Hiệu lực từ</th>
                            <th class="text-right">Mức chuẩn nghèo (CN)</th>
                            <th class="text-right">Mức tham chiếu</th>
                            <th class="text-right">Tỷ lệ đóng</th>
                            <th class="text-right">Trần đóng</th>
                            <th>Hỗ trợ theo nhóm</th>
                            <th>Nguồn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $period)
                        <tr>
                            <td class="whitespace-nowrap font-medium">{{ $period->effective_from->format('d/m/Y') }}</td>
                            <td class="text-right">{{ number_format($period->rural_poverty_line, 0, ',', '.') }} đ</td>
                            <td class="text-right">{{ number_format($period->reference_level, 0, ',', '.') }} đ</td>
                            <td class="text-right">{{ $period->contribution_rate_percent }}%</td>
                            <td class="text-right">{{ number_format($period->contributionCeiling(), 0, ',', '.') }} đ</td>
                            <td class="text-xs">
                                @foreach($period->supportTiers as $tier)
                                    <div>{{ $tier->group_key->label() }}: {{ $tier->support_percent }}%</div>
                                @endforeach
                            </td>
                            <td class="text-xs text-base-content/60">{{ $period->source_document }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-base-content/40 py-4">Chưa có giai đoạn tham số nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Hệ số trượt giá ────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body py-4 px-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-base-content">Hệ số trượt giá (Điều 10 Nghị định 159)</h2>
                @can(\App\Enums\PermissionEnum::PENSION_CALCULATOR_MANAGE->value)
                <a href="{{ route('backend.pension-calculator.price-index.create') }}" class="btn btn-primary btn-xs">+ Thêm bảng hệ số</a>
                @endcan
            </div>
            @if($coefficients->isEmpty())
            <div class="alert alert-warning text-sm">
                Chưa có hệ số trượt giá nào — trang công khai sẽ hiển thị cảnh báo "thiếu hệ số" cho mọi giai đoạn đóng góp người dùng nhập cho tới khi bổ sung (§14 mục 6).
            </div>
            @else
            <div class="overflow-x-auto max-h-72 overflow-y-auto">
                <table class="table table-sm table-pin-rows">
                    <thead>
                        <tr>
                            <th>Năm giải quyết</th>
                            <th>Năm đã đóng</th>
                            <th class="text-right">Hệ số</th>
                            <th>Nguồn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coefficients as $coef)
                        <tr>
                            <td>{{ $coef->settlement_year }}</td>
                            <td>{{ $coef->contribution_year }}</td>
                            <td class="text-right">{{ $coef->coefficient }}</td>
                            <td class="text-xs text-base-content/60">{{ $coef->source_document }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Tỷ lệ hưởng lương hưu ─────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4 px-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-base-content">Tỷ lệ hưởng lương hưu hằng tháng</h2>
                @can(\App\Enums\PermissionEnum::PENSION_CALCULATOR_MANAGE->value)
                <a href="{{ route('backend.pension-calculator.rate-brackets.create') }}" class="btn btn-primary btn-xs">+ Thêm bậc tỷ lệ</a>
                @endcan
            </div>
            @if($rateBrackets->isEmpty())
            <div class="alert alert-warning text-sm">
                Chưa cấu hình tỷ lệ hưởng lương hưu — xác minh Điều 66/99 Luật Bảo hiểm xã hội 2024 trước khi nhập số liệu (spec §14 mục 1, CHẶN go-live tính năng ước tính số tiền lương hưu cụ thể). Trang công khai sẽ chỉ hiển thị Mbq, không hiển thị số tiền lương hưu cho tới khi bảng này có dữ liệu.
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Giới tính</th>
                            <th class="text-right">Số năm tối thiểu</th>
                            <th class="text-right">Tỷ lệ nền</th>
                            <th class="text-right">+%/năm tiếp theo</th>
                            <th class="text-right">Trần</th>
                            <th>Hiệu lực từ</th>
                            <th>Nguồn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rateBrackets as $bracket)
                        <tr>
                            <td>{{ $bracket->gender === 'female' ? 'Nữ' : 'Nam' }}</td>
                            <td class="text-right">{{ $bracket->min_years_for_base_rate }}</td>
                            <td class="text-right">{{ $bracket->base_rate_percent }}%</td>
                            <td class="text-right">{{ $bracket->increment_percent_per_year }}%</td>
                            <td class="text-right">{{ $bracket->max_rate_percent }}%</td>
                            <td>{{ $bracket->effective_from->format('d/m/Y') }}</td>
                            <td class="text-xs text-base-content/60">{{ $bracket->source_document }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
