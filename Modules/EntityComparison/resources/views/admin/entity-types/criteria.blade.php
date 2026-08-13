@extends('layouts.backend')
@section('title', 'Tiêu chí')

@section('content')
<h1 class="text-2xl font-bold text-base-content mb-1">Tiêu chí — {{ $entityType->name }}</h1>
<p class="text-sm text-base-content/50 mb-5">
    Chỉ hiện các tiêu chí đã thuộc loại đối tượng này (chọn ở form tạo/sửa tiêu chí) — chỉnh bắt buộc và thứ tự hiển thị riêng cho loại này tại đây.
</p>

@if(session('success'))
<div class="alert alert-success mb-4 text-sm"><span>{{ session('success') }}</span></div>
@endif

<form action="{{ route('backend.entity_comparison.entity_types.criteria.update', $entityType) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tiêu chí</th>
                        <th>Kiểu</th>
                        <th class="text-center w-32">Bắt buộc</th>
                        <th class="text-center w-32">Thứ tự</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($criteria as $criterion)
                    <tr>
                        <td class="font-medium">
                            {{ $criterion->name }}
                            <a href="{{ route('backend.entity_comparison.criteria.edit', $criterion) }}" class="link text-xs text-base-content/40 ml-1">sửa</a>
                        </td>
                        <td class="text-base-content/50 text-xs">{{ $criterion->type->label() }}</td>
                        <td class="text-center">
                            <input type="hidden" name="is_required[{{ $criterion->id }}]" value="0">
                            <input type="checkbox" name="is_required[{{ $criterion->id }}]" value="1"
                                   class="checkbox checkbox-sm" {{ $criterion->pivot->is_required ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="number" name="sort_order[{{ $criterion->id }}]" min="0"
                                   value="{{ $criterion->pivot->sort_order }}"
                                   class="input input-bordered input-xs w-20 text-center">
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-base-content/40 py-8">
                        Chưa có tiêu chí nào thuộc loại đối tượng này —
                        <a href="{{ route('backend.entity_comparison.criteria.create') }}" class="link">tạo tiêu chí mới và chọn loại này</a>.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex gap-2 mt-4">
        <a href="{{ route('backend.entity_comparison.entity_types.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
        @if($criteria->isNotEmpty())
        <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
        @endif
    </div>
</form>
@endsection
