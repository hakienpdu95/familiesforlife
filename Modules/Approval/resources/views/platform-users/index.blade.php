@extends('layouts.backend')
@section('title', 'Quản lý nhân sự Platform')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Quản lý nhân sự Platform</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Tài khoản không thuộc tổ chức nào — đội biên tập/vận hành trung ương của Hà Kiên
            (spec/Platform_RBAC_Phase2_Specification.md §2).
        </p>
    </div>
    <a href="{{ route('backend.platform-users.create') }}" class="btn btn-primary btn-sm">Thêm nhân sự</a>
</div>

@if (session('success'))
<div class="alert alert-success text-sm mb-4">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead>
                <tr class="text-xs text-base-content/40 uppercase tracking-wide">
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                <tr class="hover">
                    <td class="font-medium">{{ $u->name }}</td>
                    <td class="text-sm text-base-content/70">{{ $u->email }}</td>
                    <td>
                        @php($roleName = $u->roles->first()?->name)
                        <span class="badge badge-ghost badge-sm">{{ $labels[$roleName] ?? $roleName ?? '—' }}</span>
                    </td>
                    <td>
                        @if ($u->is_active)
                            <span class="badge badge-success badge-sm">Hoạt động</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Đã vô hiệu hoá</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @unless ($u->hasRole('super-admin'))
                            <a href="{{ route('backend.platform-users.edit', $u) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            @if ($u->is_active && $u->id !== auth()->id())
                            <form method="POST" action="{{ route('backend.platform-users.destroy', $u) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs text-error"
                                        onclick="return confirm('Vô hiệu hoá tài khoản này?')">Vô hiệu hoá</button>
                            </form>
                            @endif
                        @else
                            <span class="text-xs text-base-content/30">Không thể sửa qua đây</span>
                        @endunless
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-sm text-base-content/40 py-6">Chưa có nhân sự Platform nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
