@extends('layouts.backend')
@section('title', $employee->full_name)


@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-4">
        @if($employee->avatar_url)
        <img src="{{ $employee->avatar_url }}" alt="{{ $employee->full_name }}"
             class="w-14 h-14 rounded-full object-cover ring-2 ring-base-300"/>
        @else
        <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">
            {{ mb_substr($employee->full_name, 0, 1) }}
        </div>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $employee->full_name }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                <span class="font-mono text-xs text-base-content/50">{{ $employee->employee_code }}</span>
                <span class="badge badge-sm {{ $employee->status->badgeClass() }}">{{ $employee->status->label() }}</span>
                <span class="badge badge-sm {{ $employee->employment_type->badgeClass() }}">{{ $employee->employment_type->label() }}</span>
                @if($employee->work_location)
                <span class="badge badge-ghost badge-sm">
                    @switch($employee->work_location)
                        @case('office') 🏢 Văn phòng @break
                        @case('remote') 🏠 Remote @break
                        @case('hybrid') 🔄 Hybrid @break
                    @endswitch
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @can('transfer', $employee)
        <a href="#" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Điều chuyển
        </a>
        @endcan
        @can('update', $employee)
        <a href="{{ route('backend.employees.edit', $employee) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Chỉnh sửa
        </a>
        @endcan
        <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>

{{-- Cảnh báo hợp đồng sắp hết hạn --}}
@if($employee->contract_end && $employee->contract_end->isFuture() && $employee->contract_end->lte(now()->addDays(90)))
@php $daysLeft = now()->diffInDays($employee->contract_end, false); @endphp
<div class="alert {{ $daysLeft <= 30 ? 'alert-error' : 'alert-warning' }} py-3 mb-5 flex items-center gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <span>Hợp đồng sắp hết hạn vào <strong>{{ $employee->contract_end->format('d/m/Y') }}</strong> — còn <strong>{{ $daysLeft }} ngày</strong>.</span>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6">

    {{-- ── Main column ──────────────────────────────────────────────────── --}}
    <div class="space-y-5">

        {{-- Vị trí & Tổ chức --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-2">Vị trí công tác</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Chi nhánh</p>
                        <p class="font-medium">{{ $employee->branch?->name ?? '—' }}</p>
                        @if($employee->branch?->code)
                        <p class="text-xs font-mono text-base-content/40">{{ $employee->branch->code }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Phòng ban</p>
                        <p class="font-medium">{{ $employee->department?->name ?? '—' }}</p>
                        @if($employee->department?->code)
                        <p class="text-xs font-mono text-base-content/40">{{ $employee->department->code }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Chức danh</p>
                        <p class="font-medium">{{ $employee->jobTitle?->name ?? '—' }}</p>
                        @if($employee->snap_job_level)
                        <p class="text-xs text-base-content/40">Level {{ $employee->snap_job_level }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Quản lý trực tiếp</p>
                        @if($employee->manager)
                        <a href="{{ route('backend.employees.show', $employee->manager) }}"
                           class="font-medium text-primary hover:underline">{{ $employee->manager->full_name }}</a>
                        <p class="text-xs font-mono text-base-content/40">{{ $employee->manager->employee_code }}</p>
                        @else
                        <p class="font-medium text-base-content/40">—</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Ngày vào làm</p>
                        <p class="font-medium">{{ $employee->hired_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @if($employee->probation_end_date)
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Kết thúc thử việc</p>
                        <p class="font-medium">{{ $employee->probation_end_date->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Hợp đồng --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-2">Hợp đồng lao động</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Loại hợp đồng</p>
                        <span class="badge badge-sm {{ $employee->employment_type->badgeClass() }}">{{ $employee->employment_type->label() }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Bắt đầu HĐ</p>
                        <p class="font-medium">{{ $employee->contract_start?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Hết hạn HĐ</p>
                        @if($employee->contract_end)
                        <p class="font-medium {{ $employee->contract_end->lte(now()->addDays(30)) ? 'text-error' : ($employee->contract_end->lte(now()->addDays(90)) ? 'text-warning' : '') }}">
                            {{ $employee->contract_end->format('d/m/Y') }}
                        </p>
                        @else
                        <p class="font-medium text-success">Không thời hạn</p>
                        @endif
                    </div>
                </div>

                @if(auth()->user()?->hasAnyRole(['System_Admin', 'HR']))
                <div class="divider my-3 text-xs text-base-content/40">Thông tin lương (HR)</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Lương cơ bản</p>
                        <p class="font-semibold text-lg">
                            @if($employee->salary_base)
                            {{ number_format($employee->salary_base, 0, ',', '.') }}
                            <span class="text-sm font-normal text-base-content/50">{{ $employee->salary_currency }}</span>
                            @else
                            <span class="text-base-content/40 text-base font-normal">Chưa cập nhật</span>
                            @endif
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Thông tin cá nhân --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-2">Thông tin cá nhân</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Email công việc</p>
                        <p class="font-medium">{{ $employee->email }}</p>
                    </div>
                    @if($employee->personal_email)
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Email cá nhân</p>
                        <p class="font-medium">{{ $employee->personal_email }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Điện thoại</p>
                        <p class="font-medium">{{ $employee->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Giới tính</p>
                        <p class="font-medium">
                            @switch($employee->gender)
                                @case('male') Nam @break
                                @case('female') Nữ @break
                                @case('other') Khác @break
                                @default —
                            @endswitch
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Ngày sinh</p>
                        <p class="font-medium">{{ $employee->date_of_birth?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Số CCCD/CMND</p>
                        <p class="font-medium font-mono">{{ $employee->national_id ?? '—' }}</p>
                        @if($employee->national_id_issued)
                        <p class="text-xs text-base-content/40">Cấp ngày {{ $employee->national_id_issued->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Mã số thuế</p>
                        <p class="font-medium font-mono">{{ $employee->tax_code ?? '—' }}</p>
                    </div>
                    @if($employee->address)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-xs text-base-content/50 mb-0.5">Địa chỉ</p>
                        <p class="font-medium">{{ $employee->address }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Liên hệ khẩn cấp --}}
        @if($employee->emergency_contact_name || $employee->emergency_contact_phone)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-2">Liên hệ khẩn cấp</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Họ tên</p>
                        <p class="font-medium">{{ $employee->emergency_contact_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Số điện thoại</p>
                        <p class="font-medium">{{ $employee->emergency_contact_phone ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Lịch sử nhân sự — Timeline --}}
        @if($employee->history->count() > 0)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Lịch sử nhân sự</h2>

                <ul class="space-y-0">
                    @foreach($employee->history->take(30) as $h)
                    @php
                        $changeTypeLabels = [
                            'hire'            => ['Tuyển dụng',         'badge-success'],
                            'branch_transfer' => ['Chuyển chi nhánh',   'badge-info'],
                            'dept_transfer'   => ['Chuyển phòng ban',   'badge-info'],
                            'promotion'       => ['Thăng chức',         'badge-primary'],
                            'demotion'        => ['Giáng chức',         'badge-warning'],
                            'manager_change'  => ['Đổi quản lý',        'badge-ghost'],
                            'salary_change'   => ['Điều chỉnh lương',   'badge-accent'],
                            'leave'           => ['Nghỉ phép',          'badge-warning'],
                            'return_from_leave'=> ['Trở lại làm việc',  'badge-success'],
                            'resign'          => ['Từ chức',            'badge-error'],
                            'terminate'       => ['Chấm dứt HĐ',       'badge-error'],
                            'separation'      => ['Thôi việc',          'badge-error'],
                        ];
                        [$ctLabel, $ctBadge] = $changeTypeLabels[$h->change_type] ?? [$h->change_type, 'badge-ghost'];
                    @endphp
                    <li class="relative pl-6 pb-5 last:pb-0">
                        {{-- Timeline line --}}
                        <span class="absolute left-[7px] top-5 bottom-0 w-px bg-base-300 last:hidden" aria-hidden="true"></span>
                        {{-- Timeline dot --}}
                        <span class="absolute left-0 top-1.5 w-3.5 h-3.5 rounded-full border-2 border-base-100 ring-1 ring-base-300 bg-base-200 {{ $loop->first ? 'bg-primary ring-primary' : '' }}"></span>

                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <span class="badge badge-sm {{ $ctBadge }}">{{ $ctLabel }}</span>
                                @if($h->note)
                                <p class="text-sm mt-1 text-base-content/70">{{ $h->note }}</p>
                                @endif

                                {{-- Chi tiết thay đổi --}}
                                <div class="text-xs text-base-content/50 mt-1.5 space-y-0.5">
                                    @if($h->old_status !== $h->new_status && $h->new_status)
                                    <p>Trạng thái: <span class="line-through">{{ $h->old_status }}</span> → <strong>{{ $h->new_status }}</strong></p>
                                    @endif
                                    @if($h->old_salary_base != $h->new_salary_base && $h->new_salary_base)
                                    <p>Lương: <span class="line-through">{{ $h->old_salary_base ? number_format($h->old_salary_base, 0, ',', '.') : '—' }}</span> → <strong>{{ number_format($h->new_salary_base, 0, ',', '.') }}</strong></p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-medium text-base-content/70">{{ $h->effective_date?->format('d/m/Y') }}</p>
                                <p class="text-xs text-base-content/40 mt-0.5">{{ $h->changedBy?->name ?? 'Hệ thống' }}</p>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>

            </div>
        </div>
        @endif

    </div>

    {{-- ── Right sidebar ─────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Quick info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm">Tóm tắt</h3>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-base-content/50">Trạng thái</span>
                        <span class="badge badge-sm {{ $employee->status->badgeClass() }}">{{ $employee->status->label() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-base-content/50">Hợp đồng</span>
                        <span class="badge badge-sm {{ $employee->employment_type->badgeClass() }}">{{ $employee->employment_type->label() }}</span>
                    </div>
                    @if($employee->snap_job_level)
                    <div class="flex justify-between items-center">
                        <span class="text-base-content/50">Cấp bậc</span>
                        <span class="font-medium">Level {{ $employee->snap_job_level }}</span>
                    </div>
                    @endif
                    @if($employee->hired_at)
                    <div class="flex justify-between items-center">
                        <span class="text-base-content/50">Vào làm</span>
                        <span class="text-xs font-medium">{{ $employee->hired_at->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    @if($employee->contract_end)
                    <div class="flex justify-between items-center">
                        <span class="text-base-content/50">Hết hạn HĐ</span>
                        <span class="text-xs font-medium {{ $employee->contract_end->lte(now()->addDays(30)) ? 'text-error' : ($employee->contract_end->lte(now()->addDays(90)) ? 'text-warning' : '') }}">
                            {{ $employee->contract_end->format('d/m/Y') }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Bank info (HR only) --}}
        @if(auth()->user()?->hasAnyRole(['System_Admin', 'HR']) && ($employee->bank_account || $employee->bank_name))
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Tài khoản ngân hàng</h3>
                <div class="text-sm space-y-2">
                    @if($employee->bank_name)
                    <p class="text-base-content/50 text-xs">Ngân hàng</p>
                    <p class="font-medium">{{ $employee->bank_name }}</p>
                    @endif
                    @if($employee->bank_account)
                    <p class="text-base-content/50 text-xs mt-1">Số tài khoản</p>
                    <p class="font-mono text-sm">{{ $employee->bank_account }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Ghi chú HR --}}
        @if(auth()->user()?->hasAnyRole(['System_Admin', 'HR']) && $employee->notes)
        <div class="card bg-base-100 shadow-sm border border-warning/30">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Ghi chú HR</h3>
                <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ $employee->notes }}</p>
            </div>
        </div>
        @endif

        {{-- Phòng ban kiêm nhiệm --}}
        @if($employee->employeeDepartments->count() > 1)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Phòng ban kiêm nhiệm</h3>
                @foreach($employee->employeeDepartments->where('left_at', null) as $ed)
                <div class="flex items-center justify-between py-1">
                    <span class="text-sm">{{ $ed->department?->name }}</span>
                    @if($ed->is_primary)
                    <span class="badge badge-primary badge-xs">Chính</span>
                    @else
                    <span class="text-xs text-base-content/40">Kiêm nhiệm</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Audit info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm text-base-content/70">Audit</h3>
                <div class="text-xs text-base-content/50 space-y-1.5">
                    <p>Tạo: {{ $employee->created_at?->format('d/m/Y H:i') }}</p>
                    @if($employee->createdBy)
                    <p>Bởi: {{ $employee->createdBy->name }}</p>
                    @endif
                    <p>Cập nhật: {{ $employee->updated_at?->format('d/m/Y H:i') }}</p>
                    @if($employee->updatedBy)
                    <p>Bởi: {{ $employee->updatedBy->name }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        @can('delete', $employee)
        <div class="card bg-base-100 shadow-sm border border-error/20">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm text-error">Vùng nguy hiểm</h3>
                <form method="POST" action="{{ route('backend.employees.destroy', $employee) }}"
                      onsubmit="return confirm('Bạn có chắc muốn xóa nhân viên {{ addslashes($employee->full_name) }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm w-full gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa nhân viên
                    </button>
                </form>
            </div>
        </div>
        @endcan

    </div>

</div>
@endsection
