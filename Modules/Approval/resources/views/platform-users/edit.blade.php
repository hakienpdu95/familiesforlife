@extends('layouts.backend')
@section('title', 'Sửa nhân sự Platform')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-base-content">Sửa: {{ $platformUser->name }}</h1>
    <a href="{{ route('backend.platform-users.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
</div>

@if($errors->any())
<div class="alert alert-error text-sm mb-5">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.platform-users.update', $platformUser) }}" class="card bg-base-100 shadow-sm border border-base-200 max-w-lg">
    @csrf
    @method('PUT')
    <div class="card-body gap-3">

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
            <input type="text" name="name" value="{{ old('name', $platformUser->name) }}" class="input input-bordered input-sm w-full @error('name') input-error @enderror">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
            <input type="email" value="{{ $platformUser->email }}" class="input input-bordered input-sm w-full" disabled>
            <p class="mt-1 text-xs text-base-content/40">Không đổi được email qua đây.</p>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò Platform <span class="text-error">*</span></span></label>
            @php($currentRole = $platformUser->roles->first()?->name)
            <select name="role" class="select select-bordered select-sm w-full @error('role') select-error @enderror">
                @foreach ($labels as $value => $label)
                    <option value="{{ $value }}" {{ old('role', $currentRole) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm mt-2">Lưu thay đổi</button>
    </div>
</form>
@endsection
