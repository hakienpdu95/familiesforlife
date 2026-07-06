@extends('layouts.backend')
@section('title', 'Chính sách nghỉ phép')


@section('content')
<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Chính sách nghỉ phép</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Cấu hình số ngày nghỉ theo loại và chức danh/phòng ban</p>
        </div>
        @can('create', \Modules\Leave\Models\LeavePolicy::class)
        <a href="{{ route('backend.leave.policies.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm chính sách
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4">
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Table --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 uppercase">
                            <th>Tên chính sách</th>
                            <th>Loại nghỉ</th>
                            <th>Số ngày/năm</th>
                            <th>Chuyển tiếp</th>
                            <th>Báo trước</th>
                            <th>Phạm vi</th>
                            <th>Hiệu lực từ</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                        <tr>
                            <td class="font-medium">{{ $policy->name }}</td>
                            <td>
                                <span class="badge badge-ghost badge-sm">{{ $policy->leave_type->label() }}</span>
                            </td>
                            <td class="tabular-nums">{{ $policy->days_per_year }}</td>
                            <td class="tabular-nums">{{ $policy->carry_over_days }}</td>
                            <td class="tabular-nums">{{ $policy->min_advance_days }} ngày</td>
                            <td class="text-xs text-base-content/60">
                                @if($policy->job_title_id)
                                    <span class="badge badge-outline badge-xs">{{ $policy->jobTitle?->name }}</span>
                                @elseif($policy->department_id)
                                    <span class="badge badge-outline badge-xs">{{ $policy->department?->name }}</span>
                                @else
                                    <span class="text-base-content/40">Toàn công ty</span>
                                @endif
                            </td>
                            <td class="tabular-nums text-sm">{{ $policy->effective_from?->format('d/m/Y') }}</td>
                            <td>
                                @if($policy->is_active)
                                    <span class="badge badge-success badge-sm">Đang áp dụng</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">Tạm dừng</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $policy)
                                    <a href="{{ route('backend.leave.policies.edit', $policy) }}"
                                       class="btn btn-ghost btn-xs">Sửa</a>
                                    @endcan
                                    @can('delete', $policy)
                                    <form method="POST" action="{{ route('backend.leave.policies.destroy', $policy) }}"
                                          onsubmit="return confirm('Xóa chính sách này?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-xs text-error">Xóa</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-base-content/40">
                                Chưa có chính sách nào. <a href="{{ route('backend.leave.policies.create') }}" class="link">Tạo mới</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
