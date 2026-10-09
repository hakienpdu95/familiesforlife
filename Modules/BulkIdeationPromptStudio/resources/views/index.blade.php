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

<div class="section-page">
    <div class="card bg-base-100 mb-4">
        <div class="card-body filter-bar py-3 px-3">
            <form method="GET" action="{{ route('backend.bulkideationpromptstudio.index') }}" class="filter-grid">

                <div class="form-control filter-grid-wide">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tên prompt — Enter để tìm</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ $search }}"
                               onchange="this.form.requestSubmit()"
                               placeholder="Nhập từ khoá tìm kiếm..." class="grow bg-transparent outline-none text-sm">
                        @if ($search !== '')
                        <a href="{{ route('backend.bulkideationpromptstudio.index') }}"
                           class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>

            </form>

            @if ($search !== '')
            <div class="flex justify-end">
                <a href="{{ route('backend.bulkideationpromptstudio.index') }}"
                   class="btn btn-ghost btn-sm gap-1.5 text-error mt-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Đặt lại
                </a>
            </div>

            <div class="flex flex-wrap gap-2 pt-3 mt-3 border-t border-base-200">
                <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                <a href="{{ route('backend.bulkideationpromptstudio.index') }}"
                   class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors">
                    <span>Tìm: {{ $search }}</span>
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
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
</div>
@endsection

@push('styles')
    @vite(['Modules/BulkIdeationPromptStudio/resources/assets/sass/bulk-ideation-prompt-studio.scss'], 'build/backend')
@endpush
