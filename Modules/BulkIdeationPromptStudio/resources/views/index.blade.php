@extends('layouts.backend')
@section('title', 'Bulk Ideation Prompt Studio')

@section('content')
<div class="mb-5 flex items-start justify-between flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Bulk Ideation Prompt Studio</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Danh sách prompt "nhào nặn ý tưởng" đã sinh từ hàng loạt từ khóa/ý tưởng vụn vặt — mỗi dòng dưới đây là 1
            prompt đã ghép sẵn để copy sang ChatGPT/Claude. Công cụ KHÔNG gọi AI trong app.
        </p>
    </div>
    <a href="{{ route('backend.bulkideationpromptstudio.create') }}" class="btn btn-primary btn-sm">+ Sinh prompt mới</a>
</div>

@if (session('success'))
    <div class="alert alert-success text-sm mb-4">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" action="{{ route('backend.bulkideationpromptstudio.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="form-control w-72">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm theo tên prompt</span></label>
                <input type="text" name="search" value="{{ $search }}"
                       class="input input-sm input-bordered w-full" placeholder="Nhập từ khoá tìm kiếm...">
            </div>
            <button type="submit" class="btn btn-sm">Tìm</button>
            @if ($search !== '')
                <a href="{{ route('backend.bulkideationpromptstudio.index') }}" class="btn btn-ghost btn-sm text-error">Đặt lại</a>
            @endif
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body py-3 px-3">
        @if ($promptList->isEmpty())
            <p class="text-sm text-base-content/50 py-6 text-center">
                @if ($search !== '')
                    Không tìm thấy prompt nào khớp "{{ $search }}".
                @else
                    Chưa có prompt nào — bấm "+ Sinh prompt mới" để bắt đầu.
                @endif
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tên gọi</th>
                            <th>Mục tiêu bài viết</th>
                            <th>Chuyên mục</th>
                            <th>Ngày tạo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promptList as $prompt)
                            <tr>
                                <td>
                                    <a href="{{ route('backend.bulkideationpromptstudio.show', $prompt) }}" class="link link-hover font-medium">
                                        {{ $prompt->label }}
                                    </a>
                                </td>
                                <td class="text-base-content/60">{{ $prompt->business_goal ?? '—' }}</td>
                                <td class="text-base-content/60">{{ $prompt->category?->name ?? '—' }}</td>
                                <td class="text-base-content/60">{{ $prompt->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('backend.bulkideationpromptstudio.show', $prompt) }}" class="btn btn-ghost btn-xs">Xem</a>
                                        <form action="{{ route('backend.bulkideationpromptstudio.destroy', $prompt) }}" method="POST"
                                              onsubmit="return confirm('Xoá prompt &quot;{{ $prompt->label }}&quot;?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Xoá</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $promptList->links() }}</div>
        @endif
    </div>
</div>
@endsection
